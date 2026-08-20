#!/usr/bin/env python3
"""Production deploy runner for the CMS.

Run on the server (from the app root or any cwd; the script finds the repo root):

    python3 deploy/production.py

Or from your laptop via SSH:

    python3 deploy/production.py remote --host <prod-host> --user <ssh-user>
    python3 deploy/production.py remote --host <prod-host> --user <ssh-user> \\
        --path /var/www/hrepoly --identity ~/.ssh/id_ed25519

Environment defaults for remote mode:
    DEPLOY_SSH_HOST, DEPLOY_SSH_USER, DEPLOY_REMOTE_PATH, DEPLOY_SSH_KEY

On failure the script stops immediately and rolls git back to the pre-pull HEAD
(database migrations/seeds are not rolled back).
"""

from __future__ import annotations

import argparse
import os
import shlex
import shutil
import subprocess
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Sequence

REPO_ROOT = Path(__file__).resolve().parent.parent
STASH_MESSAGE = "cms-deploy-auto"
DEFAULT_REMOTE_PATH = "/var/www/hrepoly"
DEFAULT_BRANCH = "main"
REQUIRED_BINARIES = ("git", "php", "composer", "npm", "bash")


class DeployError(Exception):
    """Raised when a deploy step fails."""


@dataclass
class DeployState:
    old_sha: str | None = None
    stash_created: bool = False
    pull_succeeded: bool = False
    migrate_or_seed_ran: bool = False


def log(message: str) -> None:
    print(message, flush=True)


def log_step(name: str, command: Sequence[str]) -> None:
    joined = " ".join(command)
    log(f"\n==> [{name}] {joined}")


def run_command(
    name: str,
    command: Sequence[str],
    *,
    cwd: Path = REPO_ROOT,
    check: bool = True,
    allow_nonzero: bool = False,
) -> subprocess.CompletedProcess[str]:
    """Run a command, stream output, and wait until it finishes."""
    log_step(name, command)
    started = time.monotonic()
    completed = subprocess.run(
        list(command),
        cwd=str(cwd),
        text=True,
        check=False,
    )
    elapsed = time.monotonic() - started
    log(f"    finished in {elapsed:.1f}s (exit {completed.returncode})")

    if completed.returncode != 0 and check and not allow_nonzero:
        raise DeployError(
            f"Step '{name}' failed with exit code {completed.returncode}: "
            f"{' '.join(command)}"
        )

    return completed


def git_output(*args: str) -> str:
    completed = subprocess.run(
        ["git", *args],
        cwd=str(REPO_ROOT),
        text=True,
        check=True,
        capture_output=True,
    )
    return completed.stdout.strip()


def current_sha() -> str:
    return git_output("rev-parse", "HEAD")


def preflight() -> None:
    missing = [binary for binary in REQUIRED_BINARIES if shutil.which(binary) is None]
    if missing:
        raise DeployError(
            "Missing required binaries on PATH: " + ", ".join(missing)
        )

    if not (REPO_ROOT / "artisan").is_file():
        raise DeployError(f"Laravel artisan not found under {REPO_ROOT}")


def stash_local_changes(state: DeployState) -> None:
    before = git_output("stash", "list")
    run_command(
        "git-stash",
        ["git", "stash", "push", "-u", "-m", STASH_MESSAGE],
        allow_nonzero=True,
        check=False,
    )
    after = git_output("stash", "list")
    state.stash_created = after != before and STASH_MESSAGE in after
    if state.stash_created:
        log("    stashed local changes")
    else:
        log("    no local changes to stash (or stash unchanged)")


def git_update(state: DeployState) -> None:
    state.old_sha = current_sha()
    log(f"    recorded HEAD {state.old_sha}")
    stash_local_changes(state)
    run_command("git-fetch", ["git", "fetch", "origin"])
    run_command("git-pull", ["git", "pull", "origin", DEFAULT_BRANCH])
    state.pull_succeeded = True
    log(f"    HEAD is now {current_sha()}")


def rollback(state: DeployState, error: Exception) -> None:
    log("\n!! Deploy failed — attempting git rollback (DB changes are NOT rolled back)")
    log(f"!! Cause: {error}")
    if state.migrate_or_seed_ran:
        log(
            "!! WARNING: migrate and/or seed already ran. "
            "Database may be ahead of the rolled-back code."
        )

    try:
        if state.pull_succeeded and state.old_sha:
            run_command(
                "git-rollback",
                ["git", "reset", "--hard", state.old_sha],
            )
            log(f"    reset to {state.old_sha}")
        elif state.old_sha:
            log("    pull did not succeed; skipping git reset")

        if state.stash_created:
            run_command("git-stash-pop", ["git", "stash", "pop"])
            log("    restored stashed changes")
    except Exception as rollback_error:  # noqa: BLE001 — surface rollback failures
        log(f"!! Rollback itself failed: {rollback_error}")
        raise DeployError(
            f"Deploy failed ({error}); rollback also failed ({rollback_error})"
        ) from rollback_error


def pop_stash_on_success(state: DeployState) -> None:
    if not state.stash_created:
        return
    run_command("git-stash-pop", ["git", "stash", "pop"])


def run_deploy_steps(state: DeployState) -> None:
    git_update(state)

    run_command(
        "composer-install",
        [
            "composer",
            "install",
            "--no-dev",
            "--optimize-autoloader",
            "--no-interaction",
        ],
    )

    run_command("npm-install", ["npm", "install"])

    run_command("migrate", ["php", "artisan", "migrate", "--force"])
    state.migrate_or_seed_ran = True

    run_command(
        "seed",
        [
            "php",
            "artisan",
            "db:seed",
            "--class=Database\\Seeders\\DeploymentSeeder",
            "--force",
        ],
    )

    run_command("npm-build", ["npm", "run", "build"])

    optimize_script = REPO_ROOT / "deploy" / "optimize.sh"
    run_command("optimize", ["bash", str(optimize_script)])

    run_command("queue-restart", ["php", "artisan", "queue:restart"])

    run_command(
        "supervisor-restart",
        ["sudo", "-n", "supervisorctl", "restart", "all"],
    )

    pop_stash_on_success(state)


def deploy_local() -> int:
    log(f"Production deploy starting in {REPO_ROOT}")
    state = DeployState()
    try:
        preflight()
        run_deploy_steps(state)
    except DeployError as error:
        rollback(state, error)
        return 1
    except subprocess.CalledProcessError as error:
        rollback(state, error)
        return 1
    except KeyboardInterrupt:
        rollback(state, DeployError("interrupted by user"))
        return 130

    log("\n==> Deploy completed successfully")
    return 0


def build_ssh_command(args: argparse.Namespace) -> list[str]:
    host = args.host or os.environ.get("DEPLOY_SSH_HOST")
    user = args.user or os.environ.get("DEPLOY_SSH_USER")
    remote_path = args.path or os.environ.get("DEPLOY_REMOTE_PATH", DEFAULT_REMOTE_PATH)
    identity = args.identity or os.environ.get("DEPLOY_SSH_KEY")

    if not host:
        raise DeployError(
            "Remote host is required (--host or DEPLOY_SSH_HOST)"
        )

    target = f"{user}@{host}" if user else host
    remote_command = (
        f"cd {shlex.quote(remote_path)} && python3 deploy/production.py"
    )

    ssh_cmd = ["ssh"]
    if identity:
        ssh_cmd.extend(["-i", str(Path(identity).expanduser())])
    ssh_cmd.extend(["-o", "BatchMode=yes", target, remote_command])
    return ssh_cmd


def deploy_remote(args: argparse.Namespace) -> int:
    try:
        ssh_cmd = build_ssh_command(args)
    except DeployError as error:
        log(f"!! {error}")
        return 1

    log(f"Remote deploy via: {' '.join(ssh_cmd)}")
    completed = subprocess.run(ssh_cmd, check=False)
    return completed.returncode


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Run production deploy steps sequentially with git rollback on failure.",
    )
    subparsers = parser.add_subparsers(dest="command")

    remote = subparsers.add_parser(
        "remote",
        help="SSH into production and run this script on the server",
    )
    remote.add_argument(
        "--host",
        help="SSH host (or DEPLOY_SSH_HOST)",
    )
    remote.add_argument(
        "--user",
        help="SSH user (or DEPLOY_SSH_USER)",
    )
    remote.add_argument(
        "--path",
        default=None,
        help=f"Remote app path (default: DEPLOY_REMOTE_PATH or {DEFAULT_REMOTE_PATH})",
    )
    remote.add_argument(
        "--identity",
        "-i",
        help="SSH private key path (or DEPLOY_SSH_KEY)",
    )

    return parser


def main(argv: Sequence[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    if args.command == "remote":
        return deploy_remote(args)

    return deploy_local()


if __name__ == "__main__":
    sys.exit(main())

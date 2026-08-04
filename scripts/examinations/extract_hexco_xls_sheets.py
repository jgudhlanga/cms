#!/usr/bin/env python3
"""Stream HEXCO statement worksheets from a classic .xls as NDJSON grids.

Each line is: {"name": "Sheet1", "rows": [[...], ...]}
Requires: pip install xlrd
"""

from __future__ import annotations

import argparse
import json
import sys


def cell_value(value):
    if value is None:
        return None
    if isinstance(value, float):
        if value.is_integer():
            return int(value)
        return value
    if isinstance(value, str):
        return value
    return value


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("input", help="Path to .xls workbook")
    parser.add_argument("--output", help="Write NDJSON to this file instead of stdout")
    parser.add_argument("--limit", type=int, default=0, help="Max sheets (0 = all)")
    parser.add_argument("--start", type=int, default=0, help="0-based sheet index to start from")
    args = parser.parse_args()

    try:
        import xlrd
    except ImportError:
        print("xlrd is required. Install with: pip install xlrd", file=sys.stderr)
        return 2

    out = open(args.output, "w", encoding="utf-8") if args.output else sys.stdout

    workbook = xlrd.open_workbook(args.input, on_demand=True)
    try:
        start = max(0, args.start)
        end = workbook.nsheets if args.limit <= 0 else min(workbook.nsheets, start + args.limit)

        for index in range(start, end):
            sheet = workbook.sheet_by_index(index)
            rows = []
            for row_index in range(sheet.nrows):
                rows.append([cell_value(sheet.cell_value(row_index, col)) for col in range(sheet.ncols)])
            out.write(json.dumps({"name": sheet.name, "rows": rows}, ensure_ascii=False))
            out.write("\n")
            workbook.unload_sheet(index)

            if (index + 1) % 500 == 0:
                print(f"extracted {index + 1}/{end} sheets", file=sys.stderr)
    finally:
        workbook.release_resources()
        if args.output:
            out.close()

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

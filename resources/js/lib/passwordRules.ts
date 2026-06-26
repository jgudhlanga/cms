import { z } from 'zod';

export type PasswordRuleId = 'length' | 'lower' | 'upper' | 'number' | 'symbol';

export type PasswordRule = {
    id: PasswordRuleId;
    shortLabel: string;
    ariaLabelKey: string;
    test: (password: string) => boolean;
    zodMessage: string;
};

export const PASSWORD_RULES: PasswordRule[] = [
    {
        id: 'length',
        shortLabel: '8+(length)',
        ariaLabelKey: 'trans.password_rule_min_length',
        test: (password) => password.length >= 8,
        zodMessage: 'Password must be at least 8 characters',
    },
    {
        id: 'lower',
        shortLabel: 'a-z(lowercase)',
        ariaLabelKey: 'trans.password_rule_lowercase',
        test: (password) => /[a-z]/.test(password),
        zodMessage: 'Must contain at least one lowercase letter',
    },
    {
        id: 'upper',
        shortLabel: 'A-Z(uppercase)',
        ariaLabelKey: 'trans.password_rule_uppercase',
        test: (password) => /[A-Z]/.test(password),
        zodMessage: 'Must contain at least one uppercase letter',
    },
    {
        id: 'number',
        shortLabel: '0-9(number)',
        ariaLabelKey: 'trans.password_rule_number',
        test: (password) => /[0-9]/.test(password),
        zodMessage: 'Must contain at least one number',
    },
    {
        id: 'symbol',
        shortLabel: '!@(symbol)',
        ariaLabelKey: 'trans.password_rule_symbol',
        test: (password) => /[^a-zA-Z0-9]/.test(password),
        zodMessage: 'Must contain at least one symbol',
    },
];

export type EvaluatedPasswordRule = PasswordRule & {
    passed: boolean;
};

export function evaluatePasswordRules(password: string): EvaluatedPasswordRule[] {
    return PASSWORD_RULES.map((rule) => ({
        ...rule,
        passed: rule.test(password),
    }));
}

export function buildPasswordZodSchema() {
    let schema = z.string();

    for (const rule of PASSWORD_RULES) {
        if (rule.id === 'length') {
            schema = schema.min(8, rule.zodMessage);
        } else {
            const pattern =
                rule.id === 'lower'
                    ? /[a-z]/
                    : rule.id === 'upper'
                      ? /[A-Z]/
                      : rule.id === 'number'
                        ? /[0-9]/
                        : /[^a-zA-Z0-9]/;
            schema = schema.regex(pattern, rule.zodMessage);
        }
    }

    return z.object({ password: schema });
}

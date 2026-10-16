import { Controller } from '@hotwired/stimulus';

const RESERVED = /^(true|false|null|yes|no|on|off|y|n|~)$/i;
const PLAIN_VALUE = /^[A-Za-z_/]([A-Za-z0-9_ ./()-]*[A-Za-z0-9_./()-])?$/;
const PLAIN_KEY = /^[A-Za-z_][A-Za-z0-9_-]*$/;

const isCollection = (value) => value !== null && typeof value === 'object';

function yamlString(value, pattern) {
    return pattern.test(value) && !RESERVED.test(value) ? value : JSON.stringify(value);
}

function yamlLines(value, indent) {
    const pad = ' '.repeat(indent);
    const isList = Array.isArray(value);
    const entries = isList ? value.map((item) => [null, item]) : Object.entries(value);

    if (entries.length === 0) {
        return [`${pad}${isList ? '[]' : '{}'}`];
    }

    return entries.flatMap(([key, item]) => {
        const prefix = isList ? '-' : `${yamlString(key, PLAIN_KEY)}:`;

        if (isCollection(item) && Object.keys(item).length > 0) {
            return [`${pad}${prefix}`, ...yamlLines(item, indent + 2)];
        }

        let scalar;
        if (isCollection(item)) {
            scalar = Array.isArray(item) ? '[]' : '{}';
        } else if (typeof item === 'string') {
            scalar = yamlString(item, PLAIN_VALUE);
        } else {
            scalar = String(item);
        }

        return [`${pad}${prefix} ${scalar}`];
    });
}

export default class extends Controller {
    static outlets = ['controls'];
    static targets = ['field', 'copyLabel'];
    static values = {
        iframeUrl: String,
        variant: String,
        copied: Boolean,
    };

    disconnect() {
        clearTimeout(this.timer);
    }

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.apply(), 250);
    }

    apply() {
        const overrides = {};

        this.fieldTargets.forEach((field) => {
            const row = field.closest('.controls__row');
            const read = this.read(field);
            const changed = !read.invalid && !this.isInitial(field, read.value);

            row.classList.toggle('controls__row--changed', changed);

            if (changed) {
                overrides[field.dataset.name] = read.value;
            }
        });

        this.update(Object.keys(overrides).length > 0 ? JSON.stringify(overrides) : null);
    }

    reset() {
        clearTimeout(this.timer);

        this.fieldTargets.forEach((field) => {
            if (field.disabled) {
                return;
            }

            const initial = JSON.parse(field.dataset.initialValue);

            if (field.type === 'checkbox') {
                field.checked = Boolean(initial);
            } else if (field.dataset.type === 'radio') {
                field.querySelectorAll('input').forEach((radio) => {
                    radio.checked = radio.value === String(initial ?? '');
                });
            } else if (field.dataset.type === 'json') {
                field.value = JSON.stringify(initial, null, 2);
            } else {
                field.value = initial ?? '';
            }
        });

        this.apply();
    }

    copyYaml() {
        const args = {};

        this.fieldTargets.forEach((field) => {
            const read = this.read(field);

            if (read.invalid || (this.isInitial(field, read.value) && field.dataset.inYaml !== '1')) {
                return;
            }

            args[field.dataset.name] = read.value;
        });

        const lines = ['variants:', `  ${yamlString(this.variantValue, PLAIN_KEY)}:`];
        if (Object.keys(args).length > 0) {
            lines.push('    args:', ...yamlLines(args, 6));
        } else {
            lines.push('    args: {}');
        }

        navigator.clipboard.writeText(`${lines.join('\n')}\n`);
        this.copiedValue = true;

        setTimeout(() => {
            this.copiedValue = false;
        }, 2000);
    }

    copiedValueChanged() {
        if (this.hasCopyLabelTarget) {
            this.copyLabelTarget.textContent = this.copiedValue ? 'Copied!' : 'Copy variant YAML';
        }
    }

    // Disabled fields are read-only and never produce an override
    read(field) {
        const row = field.closest('.controls__row');
        const error = row.querySelector('.controls__error');
        error.classList.add('hidden');

        if (field.disabled) {
            return { invalid: true };
        }

        switch (field.dataset.type) {
            case 'checkbox':
                return { value: field.checked };
            case 'number':
                return field.value === '' ? { invalid: true } : { value: Number(field.value) };
            case 'range': {
                const output = field.parentElement.querySelector('output');
                if (output) {
                    output.textContent = field.value;
                }

                return { value: Number(field.value) };
            }
            case 'radio': {
                const checked = field.querySelector('input:checked');

                return checked ? { value: checked.value } : { invalid: true };
            }
            case 'json':
                try {
                    const value = JSON.parse(field.value);
                    if (!isCollection(value)) {
                        throw new Error('Expected an array or an object');
                    }

                    return { value };
                } catch (e) {
                    error.textContent = e.message;
                    error.classList.remove('hidden');

                    return { invalid: true };
                }
            default:
                return { value: field.value };
        }
    }

    isInitial(field, value) {
        const initial = JSON.parse(field.dataset.initialValue);

        switch (field.dataset.type) {
            case 'select':
            case 'radio':
                return String(initial) === String(value);
            case 'range':
                return Number(initial) === value;
            case 'text':
            case 'textarea':
            case 'color':
            case 'date':
                return (initial ?? '') === value;
            case 'checkbox':
                return Boolean(initial) === value;
            default:
                return JSON.stringify(initial) === JSON.stringify(value);
        }
    }

    update(overrides) {
        const preview = new URL(this.iframeUrlValue, window.location.href);
        const shell = new URL(window.location.href);

        if (overrides === null) {
            shell.searchParams.delete('overrides');
        } else {
            preview.searchParams.set('overrides', overrides);
            shell.searchParams.set('overrides', overrides);
        }

        history.replaceState(null, '', shell);

        if (this.hasControlsOutlet) {
            this.controlsOutlet.preview(preview.pathname + preview.search);
        }
    }
}

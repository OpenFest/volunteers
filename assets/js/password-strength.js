/**
 * Password strength checker
 * Validates password strength and provides visual feedback
 */

class PasswordStrengthChecker {
    constructor(passwordInputId, confirmPasswordInputId, options = {}) {
        this.passwordInput = document.getElementById(passwordInputId);
        this.confirmPasswordInput = document.getElementById(confirmPasswordInputId);

        // Default options
        this.options = {
            minLength: options.minLength || 8,
            requireUppercase: options.requireUppercase !== false,
            requireLowercase: options.requireLowercase !== false,
            requireNumbers: options.requireNumbers !== false,
            requireSpecialChars: options.requireSpecialChars !== false,
            containerId: options.containerId || null,
            ...options
        };

        this.init();
    }

    init() {
        if (!this.passwordInput) {
            console.error('Password input not found');
            return;
        }

        // Create strength indicator
        this.createStrengthIndicator();

        // Add event listeners
        this.passwordInput.addEventListener('input', () => this.checkStrength());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());

        if (this.confirmPasswordInput) {
            this.confirmPasswordInput.addEventListener('input', () => this.checkPasswordMatch());
            this.confirmPasswordInput.addEventListener('blur', () => this.checkPasswordMatch());
        }
    }

    createStrengthIndicator() {
        const container = this.options.containerId
            ? document.getElementById(this.options.containerId)
            : this.passwordInput.parentElement;

        if (!container) return;

        // Create indicator HTML
        const indicatorHTML = `
            <div class="password-strength-indicator" style="margin-top: 8px;">
                <div class="strength-bar" style="height: 4px; background: #e0e0e0; border-radius: 2px; margin-bottom: 8px;">
                    <div class="strength-bar-fill" style="height: 100%; width: 0%; transition: all 0.3s; border-radius: 2px;"></div>
                </div>
                <div class="strength-requirements" style="font-size: 0.85em; color: #666;">
                    <div class="requirement" data-requirement="length">
                        <span class="requirement-icon">✗</span>
                        <span class="requirement-text">Минимум ${this.options.minLength} символа</span>
                    </div>
                    ${this.options.requireUppercase ? `
                    <div class="requirement" data-requirement="uppercase">
                        <span class="requirement-icon">✗</span>
                        <span class="requirement-text">Поне една главна буква</span>
                    </div>` : ''}
                    ${this.options.requireLowercase ? `
                    <div class="requirement" data-requirement="lowercase">
                        <span class="requirement-icon">✗</span>
                        <span class="requirement-text">Поне една малка буква</span>
                    </div>` : ''}
                    ${this.options.requireNumbers ? `
                    <div class="requirement" data-requirement="number">
                        <span class="requirement-icon">✗</span>
                        <span class="requirement-text">Поне една цифра</span>
                    </div>` : ''}
                    ${this.options.requireSpecialChars ? `
                    <div class="requirement" data-requirement="special">
                        <span class="requirement-icon">✗</span>
                        <span class="requirement-text">Поне един специален символ (!@#$%^&*)</span>
                    </div>` : ''}
                </div>
                <div class="strength-text" style="font-size: 0.9em; margin-top: 5px; font-weight: bold;"></div>
            </div>
        `;

        // Insert after password input or in specified container
        if (this.options.containerId) {
            container.innerHTML = indicatorHTML;
        } else {
            this.passwordInput.insertAdjacentHTML('afterend', indicatorHTML);
        }

        this.strengthBarFill = container.querySelector('.strength-bar-fill');
        this.strengthText = container.querySelector('.strength-text');
        this.requirements = container.querySelectorAll('.requirement');
    }

    checkStrength() {
        const password = this.passwordInput.value;
        const checks = this.getPasswordChecks(password);

        // Update visual indicators
        this.updateRequirements(checks);

        // Calculate strength
        const strength = this.calculateStrength(checks);
        this.updateStrengthBar(strength);

        return checks;
    }

    getPasswordChecks(password) {
        return {
            length: password.length >= this.options.minLength,
            uppercase: !this.options.requireUppercase || /[A-Z]/.test(password),
            lowercase: !this.options.requireLowercase || /[a-z]/.test(password),
            number: !this.options.requireNumbers || /[0-9]/.test(password),
            special: !this.options.requireSpecialChars || /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };
    }

    updateRequirements(checks) {
        this.requirements.forEach(req => {
            const requirement = req.dataset.requirement;
            const icon = req.querySelector('.requirement-icon');

            if (checks[requirement]) {
                icon.textContent = '✓';
                icon.style.color = '#4caf50';
                req.style.color = '#4caf50';
            } else {
                icon.textContent = '✗';
                icon.style.color = '#f44336';
                req.style.color = '#666';
            }
        });
    }

    calculateStrength(checks) {
        const total = Object.keys(checks).length;
        const passed = Object.values(checks).filter(v => v).length;
        return (passed / total) * 100;
    }

    updateStrengthBar(strength) {
        this.strengthBarFill.style.width = strength + '%';

        let color, text;
        if (strength < 40) {
            color = '#f44336';
            text = 'Слаба парола';
        } else if (strength < 70) {
            color = '#ff9800';
            text = 'Средна парола';
        } else if (strength < 100) {
            color = '#8bc34a';
            text = 'Добра парола';
        } else {
            color = '#4caf50';
            text = 'Силна парола';
        }

        this.strengthBarFill.style.backgroundColor = color;
        this.strengthText.textContent = this.passwordInput.value ? text : '';
        this.strengthText.style.color = color;
    }

    validatePassword() {
        const password = this.passwordInput.value;
        if (!password) return true;

        const checks = this.getPasswordChecks(password);
        const isValid = Object.values(checks).every(v => v);

        if (!isValid) {
            this.passwordInput.setCustomValidity('Паролата не отговаря на изискванията');
        } else {
            this.passwordInput.setCustomValidity('');
        }

        return isValid;
    }

    checkPasswordMatch() {
        if (!this.confirmPasswordInput) return true;

        const password = this.passwordInput.value;
        const confirmPassword = this.confirmPasswordInput.value;

        if (confirmPassword && password !== confirmPassword) {
            this.confirmPasswordInput.setCustomValidity('Паролите не съвпадат');
            return false;
        } else {
            this.confirmPasswordInput.setCustomValidity('');
            return true;
        }
    }

    isValid() {
        const passwordValid = this.validatePassword();
        const matchValid = this.checkPasswordMatch();
        return passwordValid && matchValid;
    }
}

// Helper function to initialize password strength checker
function initPasswordStrength(passwordInputId, confirmPasswordInputId, options = {}) {
    return new PasswordStrengthChecker(passwordInputId, confirmPasswordInputId, options);
}


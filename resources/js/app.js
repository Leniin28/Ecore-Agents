const formatMoney = (value) => `$${Number(value).toLocaleString('es-MX')}`;

document.querySelectorAll('[data-demo-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = form.querySelector('[data-form-message]');

        if (message) {
            message.textContent = form.dataset.demoMessage;
        }
    });
});

document.querySelectorAll('[data-demo-action]').forEach((button) => {
    button.addEventListener('click', () => {
        const container = button.closest('.demo-action-panel, .custom-summary');
        const message = container?.querySelector('[data-action-message]');

        if (message) {
            message.textContent = button.dataset.demoAction;
        }
    });
});

const filterLabels = {
    all: 'Mostrando todos los planes',
    basic: 'Mostrando el plan de atención básica',
    'follow-up': 'Mostrando el plan de seguimiento',
    advanced: 'Mostrando el plan de automatización avanzada',
    custom: 'Mostrando la opción personalizable',
};

const planGrid = document.querySelector('[data-plan-grid]');
const filterStatus = document.querySelector('[data-filter-status]');

document.querySelectorAll('[data-plan-filter]').forEach((button) => {
    button.addEventListener('click', () => {
        const selectedFilter = button.dataset.planFilter;

        document.querySelectorAll('[data-plan-filter]').forEach((filterButton) => {
            const isActive = filterButton === button;
            filterButton.classList.toggle('is-active', isActive);
            filterButton.setAttribute('aria-pressed', String(isActive));
        });

        planGrid?.querySelectorAll('[data-category]').forEach((card) => {
            card.hidden = selectedFilter !== 'all' && card.dataset.category !== selectedFilter;
        });

        if (filterStatus) {
            filterStatus.textContent = filterLabels[selectedFilter] ?? filterLabels.all;
        }
    });
});

const customBuilder = document.querySelector('[data-custom-builder]');

if (customBuilder) {
    const moduleInputs = [...customBuilder.querySelectorAll('.module-checkbox')];
    const extrasTotal = customBuilder.querySelector('[data-extras-total]');
    const monthlyTotal = customBuilder.querySelector('[data-monthly-total]');
    const moduleCount = customBuilder.querySelector('[data-module-count]');
    const selectedModules = customBuilder.querySelector('[data-selected-modules]');
    const recommendation = customBuilder.querySelector('[data-recommendation]');

    const renderCustomSummary = () => {
        const selected = moduleInputs.filter((input) => input.checked);
        const extraPrice = selected.reduce((total, input) => total + Number(input.dataset.modulePrice), 0);
        const plusCount = selected.filter((input) => input.dataset.moduleTier === 'plus').length;
        const advancedCount = selected.filter((input) => input.dataset.moduleTier === 'advanced').length;
        const basePrice = Number(customBuilder.dataset.basePrice);

        extrasTotal.textContent = formatMoney(extraPrice);
        monthlyTotal.textContent = formatMoney(basePrice + extraPrice);
        moduleCount.textContent = String(selected.length);
        customBuilder.querySelector('[data-plus-count]').textContent = String(plusCount);
        customBuilder.querySelector('[data-advanced-count]').textContent = String(advancedCount);
        selectedModules.replaceChildren();

        if (selected.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'empty-selection';
            emptyItem.textContent = 'Aún no has agregado funciones extra.';
            selectedModules.appendChild(emptyItem);
            recommendation.textContent = 'Selecciona las funciones que necesita tu negocio para recibir una sugerencia visual.';
            return;
        }

        selected.forEach((input) => {
            const item = document.createElement('li');
            item.textContent = `${input.dataset.moduleName} (+${formatMoney(input.dataset.modulePrice)}/mes)`;
            selectedModules.appendChild(item);
        });

        recommendation.textContent = advancedCount > plusCount
            ? 'Tu selección se acerca al nivel Avanzado por la cantidad de funciones de automatización.'
            : 'Tu selección combina la base Inicio con funciones de seguimiento tipo Plus.';
    };

    moduleInputs.forEach((input) => {
        input.addEventListener('change', () => {
            input.closest('.module-option')?.classList.toggle('is-selected', input.checked);
            renderCustomSummary();
        });
    });

    renderCustomSummary();
}

document.querySelectorAll('[data-admin-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = button.dataset.adminTarget;

        document.querySelectorAll('[data-admin-target]').forEach((menuButton) => {
            const isActive = menuButton === button;
            menuButton.classList.toggle('is-active', isActive);
            menuButton.setAttribute('aria-pressed', String(isActive));
        });

        document.querySelectorAll('[data-admin-section]').forEach((section) => {
            const isActive = section.dataset.adminSection === target;
            section.hidden = !isActive;
            section.classList.toggle('is-active', isActive);
        });
    });
});

const policyModal = document.querySelector('[data-policy-modal]');

document.querySelectorAll('[data-policy-open]').forEach((button) => {
    button.addEventListener('click', () => {
        policyModal.hidden = false;
        document.body.classList.add('modal-open');
        policyModal.querySelector('.modal-close')?.focus();
    });
});

document.querySelectorAll('[data-policy-close]').forEach((button) => {
    button.addEventListener('click', () => {
        policyModal.hidden = true;
        document.body.classList.remove('modal-open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && policyModal && !policyModal.hidden) {
        policyModal.hidden = true;
        document.body.classList.remove('modal-open');
    }
});

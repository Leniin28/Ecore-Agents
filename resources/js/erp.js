const wizard = document.querySelector('[data-order-wizard]');

if (wizard) {
    const steps = [...wizard.querySelectorAll('[data-order-step]')];
    const labels = [...wizard.querySelectorAll('[data-step-label]')];
    const client = wizard.querySelector('[data-order-client]');
    const rows = [...wizard.querySelectorAll('[data-order-line]')];
    const money = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
    let step = 0;
    const selection = () => rows.filter(row => row.querySelector('[data-product-select]').checked);
    const render = () => {
        steps.forEach((element, index) => element.hidden = step !== index);
        labels.forEach((label, index) => {
            label.classList.toggle('is-active', index === step);
            if (index === step) label.setAttribute('aria-current', 'step');
            else label.removeAttribute('aria-current');
        });
        const option = client.selectedOptions[0];
        wizard.querySelector('[data-client-detail]').textContent = option?.dataset.detail || '';
        wizard.querySelector('[data-summary-client]').textContent = option?.textContent || '';
        const summary = wizard.querySelector('[data-order-summary]');
        summary.replaceChildren();
        let total = 0;
        selection().forEach(row => {
            const quantity = Number(row.querySelector('input[type="number"]').value);
            const priceCents = Math.round(Number(row.dataset.price) * 100);
            const subtotal = priceCents * quantity;
            total += subtotal;
            const tr = document.createElement('tr');
            [row.dataset.name, String(quantity), money(priceCents / 100), money(subtotal / 100)].forEach(value => {
                const td = document.createElement('td');
                td.textContent = value;
                tr.append(td);
            });
            summary.append(tr);
        });
        wizard.querySelector('[data-order-total]').textContent = money(total / 100);
    };
    rows.forEach(row => {
        const checkbox = row.querySelector('[data-product-select]');
        const refresh = () => row.querySelectorAll('[data-line-field]').forEach(field => {
            field.disabled = !checkbox.checked;
            if (field.type === 'number') field.required = checkbox.checked;
        });
        checkbox.addEventListener('change', refresh);
        refresh();
    });
    const validate = () => {
        if (step === 0) return client.reportValidity();
        if (selection().length === 0) {
            wizard.querySelector('[data-order-message]').textContent = 'Selecciona al menos un recurso.';
            return false;
        }
        return selection().every(row => row.querySelector('input[type="number"]').reportValidity());
    };
    wizard.querySelectorAll('[data-next-step]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => { if (validate()) { step++; render(); } });
    });
    wizard.querySelectorAll('[data-previous-step]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => { step--; render(); });
    });
    client.addEventListener('change', render);
    wizard.querySelector('[data-client-search]').addEventListener('input', event => {
        const search = event.target.value.toLocaleLowerCase('es');
        [...client.options].forEach(option => option.hidden = !!option.value && !`${option.text} ${option.dataset.detail}`.toLocaleLowerCase('es').includes(search));
    });
    wizard.addEventListener('submit', event => {
        if (step !== 2) { event.preventDefault(); if (validate()) { step++; render(); } }
        else if (!selection().length) event.preventDefault();
    });
    render();
}

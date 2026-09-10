const sidebar = document.getElementById('sidebar');

function toggleSubMenu(button) {
    button.nextElementSibling.classList.toggle('show');
    button.classList.toggle('rotate');
    button.setAttribute('aria-expanded', button.classList.contains('rotate'));

}

document.querySelectorAll('[data-filter]').forEach(filter => {
    filter.addEventListener('input', () => {
        const query = filter.value.toLowerCase();
        document.querySelectorAll('[data-ticket]').forEach(ticket => {
            const status = document.querySelector('[data-status-filter]')?.value || 'All statuses';
            const matchesQuery = ticket.textContent.toLowerCase().includes(query);
            const matchesStatus = status === 'All statuses' || ticket.querySelector('.badge')?.textContent === status;
            ticket.hidden = !(matchesQuery && matchesStatus);
        });
    });
});

document.querySelectorAll('[data-status-filter]').forEach(filter => {
    filter.addEventListener('change', () => {
        document.querySelector('[data-filter]')?.dispatchEvent(new Event('input'));
    });
});

document.querySelectorAll('[data-status]').forEach(select => {
    select.addEventListener('change', () => {
        const badge = select.closest('[data-ticket]')?.querySelector('.badge');
        if (!badge) return;
        const status = select.value;
        badge.textContent = status;
        badge.className = `badge ${status === 'In Progress' ? 'progress' : status.toLowerCase()}`;
    });
});

document.querySelectorAll('[data-dismiss]').forEach(button => {
    button.addEventListener('click', () => button.closest('.notification-item')?.remove());
});

document.querySelectorAll('[data-profile-form]').forEach(form => {
    form.addEventListener('submit', event => {
        event.preventDefault();
        const message = form.querySelector('[data-form-message]');
        if (message) message.textContent = 'Profile details saved.';
    });
});

document.querySelector('[data-dismiss-all]')?.addEventListener('click', () => {
    document.querySelectorAll('.notification-item').forEach(item => item.remove());
});

document.querySelector('[data-save-ticket]')?.addEventListener('click', () => {
    const message = document.querySelector('[data-form-message]');
    if (message) message.textContent = 'Ticket updated successfully.';
});

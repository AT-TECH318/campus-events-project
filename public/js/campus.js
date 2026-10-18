(function () {
    const page = document.body.dataset.page;
    let currentUser = null;

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.content : '';
    }

    function setCsrfToken(token) {
        const meta = document.querySelector('meta[name="csrf-token"]');

        if (meta && token) {
            meta.content = token;
        }
    }

    function el(tag, text, className) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }

        return node;
    }

    function showAlert(message, type) {
        const alert = document.getElementById('page-alert');

        if (!alert) {
            return;
        }

        alert.className = 'alert alert-' + (type || 'danger');
        alert.textContent = message;
    }

    function clearAlert() {
        const alert = document.getElementById('page-alert');

        if (alert) {
            alert.className = 'alert d-none';
            alert.textContent = '';
        }
    }

    async function readPayload(response) {
        const text = await response.text();

        if (!text) {
            return null;
        }

        try {
            return JSON.parse(text);
        } catch (error) {
            return { message: 'The server returned an unexpected response.' };
        }
    }

    function fieldErrors(payload) {
        if (!payload || !payload.errors) {
            return [];
        }

        return Object.entries(payload.errors).flatMap(function (entry) {
            return entry[1].map(function (message) {
                return { field: entry[0], message: message };
            });
        });
    }

    function showFormErrors(payload) {
        const box = document.getElementById('form-errors');

        if (!box) {
            return;
        }

        const errors = fieldErrors(payload);
        box.replaceChildren();

        if (errors.length === 0) {
            box.textContent = (payload && payload.message) || 'The form could not be saved.';
        } else {
            errors.forEach(function (error) {
                box.append(el('div', error.message));
                const input = document.getElementById(error.field) || document.querySelector('[name="' + error.field + '"]');

                if (input) {
                    input.classList.add('is-invalid');
                }
            });
        }

        box.classList.remove('d-none');
    }

    function clearFormErrors() {
        const box = document.getElementById('form-errors');

        if (box) {
            box.className = 'alert alert-danger d-none';
            box.textContent = '';
        }

        document.querySelectorAll('.is-invalid').forEach(function (input) {
            input.classList.remove('is-invalid');
        });
    }

    async function api(path, options) {
        const settings = options || {};
        const headers = new Headers(settings.headers || {});
        const method = (settings.method || 'GET').toUpperCase();

        headers.set('Accept', 'application/json');

        if (settings.body && !(settings.body instanceof FormData) && !headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json');
        }

        if (method !== 'GET' && method !== 'HEAD') {
            headers.set('X-CSRF-TOKEN', csrfToken());
        }

        const response = await fetch('/api/v1' + path, {
            method: method,
            headers: headers,
            body: settings.body || null,
            credentials: 'same-origin',
        });
        const payload = await readPayload(response);

        return { response: response, payload: payload };
    }

    async function saveBrowserSession(token) {
        const response = await fetch('/browser-session', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ token: token }),
        });
        const payload = await readPayload(response);

        if (!response.ok) {
            throw new Error((payload && payload.message) || 'The sign-in session could not be saved.');
        }

        setCsrfToken(payload && payload.csrf_token);
    }

    async function clearBrowserSession() {
        const response = await fetch('/browser-session', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
        const payload = await readPayload(response);

        setCsrfToken(payload && payload.csrf_token);
    }

    function clock(value) {
        if (!value) {
            return '';
        }

        return String(value).slice(0, 5);
    }

    function dateTimeLocal(value) {
        const match = String(value || '').match(/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/);

        return match ? match[1] + 'T' + match[2] : '';
    }

    function deadlineForApi(value) {
        if (!value) {
            return '';
        }

        return value.replace('T', ' ') + ':00';
    }

    function timeForApi(value) {
        if (!value) {
            return '';
        }

        return value.length === 5 ? value + ':00' : value;
    }

    function bannerSrc(banner) {
        if (!banner || !/^events\/banners\/[A-Za-z0-9][A-Za-z0-9._-]{0,180}$/.test(banner.path || '')) {
            return '';
        }

        return '/storage/' + banner.path;
    }

    async function loadCurrentUser() {
        const result = await api('/users/me');

        currentUser = result.response.ok ? result.payload.data : null;

        return currentUser;
    }

    function navLink(href, label) {
        const item = el('li', null, 'nav-item');
        const link = el('a', label, 'nav-link');

        link.href = href;
        item.append(link);

        return item;
    }

    function renderNav() {
        const list = document.getElementById('nav-links');

        if (!list) {
            return;
        }

        const role = currentUser && currentUser.role ? currentUser.role.slug : null;
        const links = [navLink('/', 'Events')];

        if (!currentUser) {
            links.push(navLink('/login', 'Log in'), navLink('/register', 'Register'));
        } else {
            links.push(navLink('/registrations', 'My registrations'));

            if (role === 'organizer' || role === 'administrator') {
                links.push(navLink('/organizer/events', 'Manage events'));
            }

            if (role === 'administrator') {
                links.push(navLink('/admin/categories', 'Categories'), navLink('/admin/users', 'Users'));
            }

            const item = el('li', null, 'nav-item');
            const button = el('button', 'Log out', 'btn btn-outline-light btn-sm');

            button.type = 'button';
            button.addEventListener('click', logout);
            item.append(button);
            links.push(item);
        }

        list.replaceChildren.apply(list, links);
    }

    async function logout() {
        const button = document.querySelector('#nav-links button');

        if (button) {
            button.disabled = true;
            button.textContent = 'Logging out…';
        }

        await api('/logout', { method: 'POST' });
        await clearBrowserSession();
        window.location.assign('/');
    }

    async function signIn(path, body) {
        clearFormErrors();
        const button = document.querySelector('form button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.textContent = 'Please wait…';
        }

        const result = await api(path, {
            method: 'POST',
            body: JSON.stringify(body),
        });

        if (!result.response.ok || !result.payload || !result.payload.token) {
            showFormErrors(result.payload);

            if (button) {
                button.disabled = false;
                button.textContent = path === '/login' ? 'Log in' : 'Create account';
            }

            return;
        }

        try {
            await saveBrowserSession(result.payload.token);
        } catch (error) {
            showFormErrors({ message: error.message });

            if (button) {
                button.disabled = false;
            }

            return;
        }

        result.payload.token = '';
        window.location.assign('/');
    }

    function queryFromFilters(pageNumber) {
        const form = document.getElementById('event-filters');
        const data = new FormData(form);
        const params = new URLSearchParams();

        ['title', 'organizer', 'category', 'date', 'timeframe'].forEach(function (name) {
            const value = String(data.get(name) || '').trim();

            if (value !== '') {
                params.set(name, value);
            }
        });

        params.set('per_page', '12');
        params.set('page', String(pageNumber || 1));

        return params;
    }

    function renderEventCard(event) {
        const column = el('div', null, 'col-12 col-md-6 col-xl-4');
        const card = el('article', null, 'card event-card h-100');
        const body = el('div', null, 'card-body d-flex flex-column');
        const title = el('h2', event.title);
        const meta = el('ul', null, 'meta-list mb-3');

        meta.append(
            el('li', (event.event_date || '') + ' · ' + clock(event.start_time) + '–' + clock(event.end_time)),
            el('li', event.venue || ''),
            el('li', event.category && event.category.name ? event.category.name : 'Uncategorised'),
            el('li', event.organizer && event.organizer.name ? 'Organizer: ' + event.organizer.name : '')
        );

        const link = el('a', 'View details', 'btn btn-outline-primary mt-auto');

        link.href = '/events/' + encodeURIComponent(event.id);
        body.append(title, meta, link);
        card.append(body);
        column.append(card);

        return column;
    }

    function renderPager(meta, load) {
        const pager = document.getElementById('event-pager');

        pager.replaceChildren();

        if (!meta) {
            return;
        }

        const label = el('span', 'Page ' + meta.current_page + ' of ' + meta.last_page);
        const controls = el('div', null, 'd-flex gap-2');
        const previous = el('button', 'Previous', 'btn btn-outline-secondary');
        const next = el('button', 'Next', 'btn btn-outline-secondary');

        previous.type = 'button';
        next.type = 'button';
        previous.disabled = meta.current_page <= 1;
        next.disabled = meta.current_page >= meta.last_page;
        previous.addEventListener('click', function () {
            load(meta.current_page - 1);
        });
        next.addEventListener('click', function () {
            load(meta.current_page + 1);
        });
        controls.append(previous, next);
        pager.append(label, controls);
    }

    async function loadCategories(select, selected) {
        const result = await api('/categories?per_page=100');

        if (!result.response.ok) {
            throw new Error((result.payload && result.payload.message) || 'Categories could not be loaded.');
        }

        (result.payload.data || []).forEach(function (category) {
            const option = el('option', category.name);

            option.value = select.id === 'filter-category' ? category.slug : String(category.id);

            if (String(selected || '') === option.value) {
                option.selected = true;
            }

            select.append(option);
        });
    }

    async function renderHome() {
        const status = document.getElementById('event-status');
        const list = document.getElementById('event-list');
        const form = document.getElementById('event-filters');
        const categorySelect = document.getElementById('filter-category');

        async function load(pageNumber) {
            clearAlert();
            status.textContent = 'Loading events…';
            list.replaceChildren();

            try {
                const result = await api('/events?' + queryFromFilters(pageNumber).toString());

                if (!result.response.ok) {
                    status.textContent = '';
                    showAlert((result.payload && result.payload.message) || 'Events could not be loaded.', 'danger');

                    return;
                }

                const events = result.payload.data || [];

                status.textContent = events.length === 0 ? 'No events match these filters.' : events.length + ' event' + (events.length === 1 ? '' : 's') + ' on this page.';
                events.forEach(function (event) {
                    list.append(renderEventCard(event));
                });
                renderPager(result.payload.meta, load);
            } catch (error) {
                status.textContent = '';
                showAlert('Events could not be loaded. Check your connection and try again.', 'danger');
            }
        }

        try {
            await loadCategories(categorySelect);
        } catch (error) {
            showAlert(error.message, 'danger');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            load(1);
        });
        document.getElementById('clear-filters').addEventListener('click', function () {
            form.reset();
            document.getElementById('filter-timeframe').value = 'upcoming';
            load(1);
        });
        load(1);
    }

    async function renderEvent() {
        const status = document.getElementById('event-status');
        const article = document.getElementById('event-detail');
        const eventId = document.body.dataset.eventId;
        const eventResult = await api('/events/' + encodeURIComponent(eventId));

        if (!eventResult.response.ok) {
            status.textContent = '';
            showAlert((eventResult.payload && eventResult.payload.message) || 'This event could not be loaded.', eventResult.response.status === 403 ? 'warning' : 'danger');

            return;
        }

        const event = eventResult.payload.data;
        let registered = false;

        if (currentUser) {
            const registrations = await api('/users/me/registrations?per_page=100');

            if (registrations.response.ok) {
                registered = (registrations.payload.data || []).some(function (row) {
                    return String(row.event_id) === String(event.id);
                });
            }
        }

        status.remove();
        article.append(el('h1', event.title));

        const imageSrc = bannerSrc(event.banner);

        if (imageSrc) {
            const image = el('img', null, 'event-banner');

            image.src = imageSrc;
            image.alt = 'Banner for ' + event.title;
            article.append(image);
        }

        article.append(el('p', event.description, 'mt-3'));

        const meta = el('ul', null, 'meta-list mb-4');

        meta.append(
            el('li', 'Date: ' + (event.event_date || '')),
            el('li', 'Time: ' + clock(event.start_time) + '–' + clock(event.end_time)),
            el('li', 'Venue: ' + (event.venue || '')),
            el('li', 'Category: ' + ((event.category && event.category.name) || 'Uncategorised')),
            el('li', 'Organizer: ' + ((event.organizer && event.organizer.name) || '')),
            el('li', 'Maximum capacity: ' + event.maximum_capacity),
            el('li', 'Registration deadline: ' + (event.registration_deadline || '')),
            el('li', 'Status: ' + (event.status || ''))
        );
        article.append(meta);

        const actions = el('div', null, 'd-flex flex-wrap gap-2');

        if (!currentUser) {
            const link = el('a', 'Log in to register', 'btn btn-primary');

            link.href = '/login';
            actions.append(link);
        } else if (registered) {
            const button = el('button', 'Cancel registration', 'btn btn-outline-danger');

            button.type = 'button';
            button.addEventListener('click', function () {
                changeRegistration(button, 'DELETE', 'Cancelling…', 'Cancel registration');
            });
            actions.append(button);
        } else {
            const button = el('button', 'Register', 'btn btn-primary');

            button.type = 'button';
            button.addEventListener('click', function () {
                changeRegistration(button, 'POST', 'Registering…', 'Register');
            });
            actions.append(button);
        }

        article.append(actions);

        async function changeRegistration(button, method, pendingText, doneText) {
            clearAlert();
            button.disabled = true;
            button.textContent = pendingText;
            const result = await api('/events/' + encodeURIComponent(event.id) + '/registrations', { method: method });

            if (!result.response.ok) {
                button.disabled = false;
                button.textContent = doneText;
                showAlert((result.payload && result.payload.message) || 'The registration could not be updated.', 'danger');

                return;
            }

            window.location.reload();
        }
    }

    function bindAuth(formId, path, fields) {
        const form = document.getElementById(formId);

        if (!form) {
            return;
        }

        if (currentUser) {
            window.location.assign('/');

            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const body = {};

            fields.forEach(function (field) {
                body[field] = form.elements[field].value;
            });
            signIn(path, body);
        });
    }

    async function renderRegistrations() {
        const status = document.getElementById('registration-status');
        const list = document.getElementById('registration-list');

        if (!currentUser) {
            status.textContent = '';
            showAlert('Log in to see your registrations.', 'warning');
            const link = el('a', 'Log in', 'btn btn-primary');

            link.href = '/login';
            list.append(link);

            return;
        }

        const result = await api('/users/me/registrations?per_page=100');

        if (!result.response.ok) {
            status.textContent = '';
            showAlert((result.payload && result.payload.message) || 'Registrations could not be loaded.', 'danger');

            return;
        }

        const rows = result.payload.data || [];

        status.textContent = rows.length === 0 ? 'You have no registrations yet.' : '';
        rows.forEach(function (row) {
            const card = el('article', null, 'card');
            const body = el('div', null, 'card-body d-flex flex-wrap justify-content-between gap-3');
            const copy = el('div');
            const event = row.event || {};

            copy.append(el('h2', event.title || 'Event', 'h5'), el('p', (event.event_date || '') + ' · ' + (event.venue || ''), 'mb-0 text-secondary'));

            const actions = el('div', null, 'd-flex flex-wrap gap-2');
            const view = el('a', 'View event', 'btn btn-outline-primary');
            const cancel = el('button', 'Cancel registration', 'btn btn-outline-danger');

            view.href = '/events/' + encodeURIComponent(row.event_id);
            cancel.type = 'button';
            cancel.addEventListener('click', async function () {
                clearAlert();
                cancel.disabled = true;
                cancel.textContent = 'Cancelling…';
                const response = await api('/events/' + encodeURIComponent(row.event_id) + '/registrations', { method: 'DELETE' });

                if (!response.response.ok) {
                    cancel.disabled = false;
                    cancel.textContent = 'Cancel registration';
                    showAlert((response.payload && response.payload.message) || 'The registration could not be cancelled.', 'danger');

                    return;
                }

                card.remove();

                if (!list.children.length) {
                    status.textContent = 'You have no registrations yet.';
                }
            });
            actions.append(view, cancel);
            body.append(copy, actions);
            card.append(body);
            list.append(card);
        });
    }

    async function renderOrganizerEvents() {
        const status = document.getElementById('manage-status');
        const list = document.getElementById('manage-list');
        const createLink = document.getElementById('create-event-link');
        const role = currentUser && currentUser.role ? currentUser.role.slug : null;

        if (role !== 'organizer' && role !== 'administrator') {
            status.textContent = '';
            createLink.classList.add('d-none');
            showAlert('Only organizers and administrators can manage events.', 'warning');

            return;
        }

        const path = role === 'administrator' ? '/events?per_page=100' : '/events?mine=1&per_page=100';
        const result = await api(path);

        if (!result.response.ok) {
            status.textContent = '';
            showAlert((result.payload && result.payload.message) || 'Events could not be loaded.', 'danger');

            return;
        }

        const events = result.payload.data || [];

        status.textContent = events.length === 0 ? 'No events to manage yet.' : '';
        events.forEach(function (event) {
            const card = el('article', null, 'card');
            const body = el('div', null, 'card-body d-flex flex-wrap justify-content-between gap-3');
            const copy = el('div');
            const badge = el('span', event.status || '', 'badge text-bg-secondary badge-status ms-2');

            const heading = el('h2', event.title, 'h5 d-inline');

            copy.append(heading, badge, el('p', (event.event_date || '') + ' · ' + (event.venue || ''), 'mb-0 mt-2 text-secondary'));

            const actions = el('div', null, 'd-flex flex-wrap gap-2');
            const view = el('a', 'View', 'btn btn-outline-primary');
            const edit = el('a', 'Edit', 'btn btn-outline-secondary');
            const remove = el('button', 'Delete', 'btn btn-outline-danger');

            view.href = '/events/' + encodeURIComponent(event.id);
            edit.href = '/organizer/events/' + encodeURIComponent(event.id) + '/edit';
            remove.type = 'button';
            remove.addEventListener('click', async function () {
                if (!window.confirm('Delete this event?')) {
                    return;
                }

                remove.disabled = true;
                remove.textContent = 'Deleting…';
                const response = await api('/events/' + encodeURIComponent(event.id), { method: 'DELETE' });

                if (!response.response.ok && response.response.status !== 204) {
                    remove.disabled = false;
                    remove.textContent = 'Delete';
                    showAlert((response.payload && response.payload.message) || 'The event could not be deleted.', 'danger');

                    return;
                }

                card.remove();

                if (!list.children.length) {
                    status.textContent = 'No events to manage yet.';
                }
            });
            actions.append(view, edit, remove);
            body.append(copy, actions);
            card.append(body);
            list.append(card);
        });
    }

    function eventPayload(form) {
        return {
            title: form.elements.title.value.trim(),
            description: form.elements.description.value.trim(),
            category_id: Number(form.elements.category_id.value),
            venue: form.elements.venue.value.trim(),
            event_date: form.elements.event_date.value,
            start_time: timeForApi(form.elements.start_time.value),
            end_time: timeForApi(form.elements.end_time.value),
            maximum_capacity: Number(form.elements.maximum_capacity.value),
            registration_deadline: deadlineForApi(form.elements.registration_deadline.value),
            status: form.elements.status.value,
        };
    }

    async function renderEventForm() {
        const form = document.getElementById('event-form');
        const status = document.getElementById('form-status');
        const eventId = document.body.dataset.eventId || '';
        const role = currentUser && currentUser.role ? currentUser.role.slug : null;

        if (role !== 'organizer' && role !== 'administrator') {
            status.textContent = '';
            showAlert('Only organizers and administrators can save events.', 'warning');

            return;
        }

        try {
            await loadCategories(form.elements.category_id);
        } catch (error) {
            status.textContent = '';
            showAlert(error.message, 'danger');

            return;
        }

        if (eventId) {
            const result = await api('/events/' + encodeURIComponent(eventId));

            if (!result.response.ok) {
                status.textContent = '';
                showAlert((result.payload && result.payload.message) || 'The event could not be loaded.', 'danger');

                return;
            }

            const event = result.payload.data;

            form.elements.title.value = event.title || '';
            form.elements.description.value = event.description || '';
            form.elements.category_id.value = event.category ? String(event.category.id) : '';
            form.elements.venue.value = event.venue || '';
            form.elements.event_date.value = event.event_date || '';
            form.elements.start_time.value = clock(event.start_time);
            form.elements.end_time.value = clock(event.end_time);
            form.elements.maximum_capacity.value = event.maximum_capacity || '';
            form.elements.registration_deadline.value = dateTimeLocal(event.registration_deadline);
            form.elements.status.value = event.status || 'draft';

            const preview = document.getElementById('banner-preview');
            const imageSrc = bannerSrc(event.banner);

            if (imageSrc && preview) {
                preview.src = imageSrc;
                preview.alt = 'Current banner for ' + event.title;
                preview.classList.remove('d-none');
            }
        }

        status.textContent = '';
        form.classList.remove('d-none');
        form.addEventListener('submit', async function (submitEvent) {
            submitEvent.preventDefault();
            clearFormErrors();
            const button = document.getElementById('save-event');

            button.disabled = true;
            button.textContent = 'Saving…';

            const payload = eventPayload(form);
            const file = form.elements.banner.files[0];
            let body;

            if (file) {
                body = new FormData();
                Object.entries(payload).forEach(function (entry) {
                    body.append(entry[0], entry[1]);
                });
                body.append('banner', file);
            } else {
                body = JSON.stringify(payload);
            }

            const result = await api(eventId ? '/events/' + encodeURIComponent(eventId) : '/events', {
                method: eventId ? 'PATCH' : 'POST',
                body: body,
            });

            if (!result.response.ok) {
                button.disabled = false;
                button.textContent = 'Save event';
                showFormErrors(result.payload);

                return;
            }

            window.location.assign('/organizer/events');
        });

        const remove = document.getElementById('delete-event');

        if (remove) {
            remove.addEventListener('click', async function () {
                if (!window.confirm('Delete this event?')) {
                    return;
                }

                remove.disabled = true;
                remove.textContent = 'Deleting…';
                const result = await api('/events/' + encodeURIComponent(eventId), { method: 'DELETE' });

                if (!result.response.ok && result.response.status !== 204) {
                    remove.disabled = false;
                    remove.textContent = 'Delete event';
                    showAlert((result.payload && result.payload.message) || 'The event could not be deleted.', 'danger');

                    return;
                }

                window.location.assign('/organizer/events');
            });
        }
    }

    async function renderCategories() {
        const status = document.getElementById('category-status');
        const list = document.getElementById('category-list');
        const form = document.getElementById('category-form');
        let editingId = null;

        if (!currentUser || !currentUser.role || currentUser.role.slug !== 'administrator') {
            status.textContent = '';
            form.classList.add('d-none');
            showAlert('Only administrators can manage categories.', 'warning');

            return;
        }

        async function load() {
            status.textContent = 'Loading categories…';
            list.replaceChildren();
            const result = await api('/categories?per_page=100');

            if (!result.response.ok) {
                status.textContent = '';
                showAlert((result.payload && result.payload.message) || 'Categories could not be loaded.', 'danger');

                return;
            }

            const categories = result.payload.data || [];

            status.textContent = categories.length === 0 ? 'No categories yet.' : '';
            categories.forEach(function (category) {
                const card = el('article', null, 'card');
                const body = el('div', null, 'card-body d-flex flex-wrap justify-content-between gap-3');
                const copy = el('div');

                copy.append(el('h2', category.name, 'h5'), el('p', category.description || category.slug, 'mb-0 text-secondary'));

                const actions = el('div', null, 'd-flex gap-2');
                const edit = el('button', 'Edit', 'btn btn-outline-secondary');
                const remove = el('button', 'Delete', 'btn btn-outline-danger');

                edit.type = 'button';
                remove.type = 'button';
                edit.addEventListener('click', function () {
                    editingId = category.id;
                    form.elements.name.value = category.name || '';
                    form.elements.description.value = category.description || '';
                    document.getElementById('category-form-title').textContent = 'Edit category';
                    document.getElementById('cancel-category-edit').classList.remove('d-none');
                });
                remove.addEventListener('click', async function () {
                    if (!window.confirm('Delete this category?')) {
                        return;
                    }

                    remove.disabled = true;
                    remove.textContent = 'Deleting…';
                    const response = await api('/categories/' + encodeURIComponent(category.id), { method: 'DELETE' });

                    if (!response.response.ok && response.response.status !== 204) {
                        remove.disabled = false;
                        remove.textContent = 'Delete';
                        showAlert((response.payload && response.payload.message) || 'The category could not be deleted.', 'danger');

                        return;
                    }

                    load();
                });
                actions.append(edit, remove);
                body.append(copy, actions);
                card.append(body);
                list.append(card);
            });
        }

        document.getElementById('cancel-category-edit').addEventListener('click', function () {
            editingId = null;
            form.reset();
            document.getElementById('category-form-title').textContent = 'Add a category';
            this.classList.add('d-none');
        });
        form.addEventListener('submit', async function (submitEvent) {
            submitEvent.preventDefault();
            clearFormErrors();
            const button = form.querySelector('button[type="submit"]');

            button.disabled = true;
            button.textContent = 'Saving…';
            const body = JSON.stringify({
                name: form.elements.name.value.trim(),
                description: form.elements.description.value.trim(),
            });
            const result = await api(editingId ? '/categories/' + encodeURIComponent(editingId) : '/categories', {
                method: editingId ? 'PATCH' : 'POST',
                body: body,
            });

            button.disabled = false;
            button.textContent = 'Save';

            if (!result.response.ok) {
                showFormErrors(result.payload);

                return;
            }

            editingId = null;
            form.reset();
            document.getElementById('category-form-title').textContent = 'Add a category';
            document.getElementById('cancel-category-edit').classList.add('d-none');
            load();
        });
        load();
    }

    async function renderUsers() {
        const status = document.getElementById('user-status');
        const list = document.getElementById('user-list');

        if (!currentUser || !currentUser.role || currentUser.role.slug !== 'administrator') {
            status.textContent = '';
            showAlert('Only administrators can manage users.', 'warning');

            return;
        }

        const result = await api('/users?per_page=100');

        if (!result.response.ok) {
            status.textContent = '';
            showAlert((result.payload && result.payload.message) || 'Users could not be loaded.', 'danger');

            return;
        }

        const users = result.payload.data || [];

        status.textContent = users.length === 0 ? 'No users found.' : '';
        users.forEach(function (user) {
            const card = el('article', null, 'card');
            const body = el('div', null, 'card-body');
            const row = el('div', null, 'row g-3 align-items-end');
            const identity = el('div', null, 'col-12 col-md-6');

            identity.append(el('h2', user.name, 'h5'), el('p', user.email, 'mb-0 text-secondary'));

            const roleColumn = el('div', null, 'col-12 col-md-4');
            const label = el('label', 'Role', 'form-label');
            const select = el('select', null, 'form-select');
            const selectId = 'role-' + user.id;

            label.htmlFor = selectId;
            select.id = selectId;
            ['student', 'organizer', 'administrator'].forEach(function (slug) {
                const option = el('option', slug.charAt(0).toUpperCase() + slug.slice(1));

                option.value = slug;

                if (user.role && user.role.slug === slug) {
                    option.selected = true;
                }

                select.append(option);
            });
            roleColumn.append(label, select);

            const action = el('div', null, 'col-12 col-md-2');
            const button = el('button', 'Update role', 'btn btn-primary');

            button.type = 'button';
            button.addEventListener('click', async function () {
                clearAlert();
                button.disabled = true;
                button.textContent = 'Saving…';
                const response = await api('/users/' + encodeURIComponent(user.id) + '/role', {
                    method: 'PATCH',
                    body: JSON.stringify({ role: select.value }),
                });

                button.disabled = false;
                button.textContent = 'Update role';

                if (!response.response.ok) {
                    showAlert((response.payload && response.payload.message) || 'The role could not be changed.', 'danger');

                    return;
                }

                showAlert('Role updated for ' + user.name + '.', 'success');
            });
            action.append(button);
            row.append(identity, roleColumn, action);
            body.append(row);
            card.append(body);
            list.append(card);
        });
    }

    const pages = {
        home: renderHome,
        event: renderEvent,
        login: function () {
            bindAuth('login-form', '/login', ['email', 'password']);
        },
        register: function () {
            bindAuth('register-form', '/register', ['name', 'email', 'password', 'password_confirmation']);
        },
        registrations: renderRegistrations,
        'organizer-events': renderOrganizerEvents,
        'organizer-event-form': renderEventForm,
        'admin-categories': renderCategories,
        'admin-users': renderUsers,
    };

    document.addEventListener('DOMContentLoaded', async function () {
        try {
            await loadCurrentUser();
        } catch (error) {
            currentUser = null;
        }

        renderNav();

        if (pages[page]) {
            await pages[page]();
        }
    });
})();

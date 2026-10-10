(function () {
    'use strict';

    function carLabel(index) {
        let label = '';
        do {
            label = String.fromCharCode(65 + index % 26) + label;
            index = Math.floor(index / 26) - 1;
        } while (index >= 0);
        return '車' + label;
    }

    function validate(data, final = true) {
        for (const [index, group] of [...data.cars, data.walk].entries()) {
            if (index < data.cars.length) {
                if (!group.length || !final) continue;
                if (!group.some(p => p.is_driver === '1')) return '各車にドライバーが必要です。ドライバーを移すか、メンバーを徒歩へ移してください。';
                if (group.length === 2 && group[0].gender !== group[1].gender && group[0].family_id !== group[1].family_id) {
                    return '家族が異なる男女を2人だけの車にはできません。';
                }
            }
        }
        return '';
    }

    // 候補を複製してから変更する。失敗した操作で現在の配置を壊さない。
    function edit(data, sourceId, targetGroup) {
        const next = {cars: data.cars.map(g => g.slice()), walk: data.walk.slice(), locked: (data.locked || []).slice()};
        const groups = [...next.cars, next.walk];
        const locate = id => {
            for (const group of groups) {
                const index = group.findIndex(p => String(p.id) === String(id));
                if (index !== -1) return {group, index};
            }
            return null;
        };
        const source = locate(sourceId);
        if (!source) return {error: 'メンバーが見つかりません。'};
        const group = targetGroup === 'walk' ? next.walk : next.cars[Number(targetGroup)];
        if (!group) return {error: '移動先が見つかりません。'};
        if (next.locked.some(index => next.cars[Number(index)] === source.group || next.cars[Number(index)] === group)) {
            return {error: '固定中の車は変更できません。固定解除してください。'};
        }
        if (group !== source.group) group.push(...source.group.splice(source.index, 1));
        const error = validate(next, false);
        if (error) return {error};
        next.cars.forEach(g => g.sort((a, b) => Number(b.is_driver === '1') - Number(a.is_driver === '1')));
        return {data: next};
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = {validate, edit, carLabel};
    if (typeof document === 'undefined') return;
    const root = document.getElementById('pairing-editor');
    if (!root) return;
    const input = document.getElementById('pairing-result');
    const message = document.getElementById('pairing-edit-message');
    let data = JSON.parse(input.value);
    data.locked = data.locked || [];
    let selected = null;
    const rows = new Map([...root.querySelectorAll('[data-member-id]')].map(row => [row.dataset.memberId, row]));

    function clearSelection() {
        selected = null;
        rows.forEach(row => {
            row.classList.remove('is-selected');
            row.setAttribute('aria-pressed', 'false');
        });
        root.querySelectorAll('.drop-target').forEach(card => card.classList.remove('drop-target'));
    }
    function select(row) {
        if (data.locked.includes(row.closest('[data-group]').dataset.group)) {
            message.textContent = '固定中の車は変更できません。固定解除してください。';
            return;
        }
        clearSelection();
        selected = row.dataset.memberId;
        row.classList.add('is-selected');
        row.setAttribute('aria-pressed', 'true');
        message.textContent = '移動先のグループを選んでください。Escで取り消せます。';
    }
    function renderLocks() {
        root.querySelectorAll('[data-group]').forEach(card => {
            const locked = data.locked.includes(card.dataset.group);
            card.classList[locked ? 'add' : 'remove']('is-locked');
            const button = card.querySelector('.group-lock');
            if (button) {
                button.textContent = locked ? '固定解除' : '決定';
                button.setAttribute('aria-pressed', String(locked));
                button.disabled = !data.cars[Number(card.dataset.group)].length;
            }
            card.querySelectorAll('[data-member-id]').forEach(row => row.setAttribute('draggable', String(!locked)));
        });
        input.value = JSON.stringify(data);
    }
    renderLocks();
    function render() {
        root.querySelectorAll('[data-group]').forEach(card => {
            const group = card.dataset.group === 'walk' ? data.walk : data.cars[Number(card.dataset.group)];
            group.forEach(p => card.querySelector('.passenger-list').appendChild(rows.get(String(p.id))));
            card.querySelector('.badge').textContent = group.length + '人';
        });
        renderLocks();
        document.getElementById('pairing-score').textContent = '手動調整済み（履歴スコアは未計算）';
    }
    function apply(target) {
        const card = target.closest('[data-group]');
        if (!card || selected === null) return;
        const outcome = edit(data, selected, card.dataset.group);
        clearSelection();
        if (outcome.error) {
            message.textContent = outcome.error + ' 変更を取り消しました。';
            return;
        }
        data = outcome.data;
        render();
        const warning = validate(data);
        message.textContent = warning ? '移動しました。決定前に調整してください：' + warning :
            '組合せを変更しました。「この組合せで決定」で保存できます。';
    }
    document.getElementById('add-pairing-group').addEventListener('click', () => {
        const index = data.cars.length;
        data.cars.push([]);
        const card = document.createElement('div');
        card.className = 'car-card';
        card.dataset.group = String(index);
        card.tabIndex = 0;
        card.setAttribute('aria-label', carLabel(index) + 'に移動');
        const header = document.createElement('div');
        header.className = 'car-header';
        const title = document.createElement('h3');
        title.textContent = '🚗 ' + carLabel(index);
        const badge = document.createElement('span');
        badge.className = 'badge';
        const list = document.createElement('ul');
        list.className = 'passenger-list';
        header.appendChild(title);
        header.appendChild(badge);
        card.appendChild(header);
        card.appendChild(list);
        const lock = document.createElement('button');
        lock.type = 'button';
        lock.className = 'btn btn-outline group-lock';
        card.appendChild(lock);
        root.insertBefore(card, root.querySelector('[data-group="walk"]'));
        render();
        message.textContent = '空グループを追加しました。ここへメンバーを移動できます。';
        // 選択中のメンバーは保持し、タップでもすぐ新しい枠へ移動できるようにする。
        card.focus();
    });
    root.addEventListener('dragstart', event => {
        const row = event.target.closest('[data-member-id]');
        if (!row) return;
        if (data.locked.includes(row.closest('[data-group]').dataset.group)) {
            event.preventDefault();
            clearSelection();
            message.textContent = '固定中の車は変更できません。固定解除してください。';
            return;
        }
        select(row);
        if (selected === null) { event.preventDefault(); return; }
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', selected);
    });
    root.addEventListener('dragover', event => {
        const card = event.target.closest('[data-group]');
        if (selected === null || !card) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        root.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
        card.classList.add('drop-target');
    });
    root.addEventListener('drop', event => {
        event.preventDefault();
        apply(event.target);
    });
    root.addEventListener('dragend', () => {
        if (selected !== null) message.textContent = 'ドラッグ操作を取り消しました。';
        clearSelection();
    });
    root.addEventListener('click', event => {
        const lock = event.target.closest('.group-lock');
        if (lock) {
            const index = lock.closest('[data-group]').dataset.group;
            const isLocked = data.locked.includes(index);
            if (!isLocked) {
                const group = data.cars[Number(index)];
                const error = !group.length ? '空の車は固定できません。' : validate({cars: [group], walk: []});
                if (error) { message.textContent = error; return; }
            }
            data.locked = isLocked ? data.locked.filter(i => i !== index) : [...data.locked, index];
            clearSelection();
            renderLocks();
            message.textContent = isLocked ? '固定を解除しました。' : 'この車を固定しました。組合せをやり直してもメンバーは変わりません。';
            return;
        }
        const row = event.target.closest('[data-member-id]');
        if (selected === null) {
            if (row) select(row);
        } else if (row && row.dataset.memberId === selected) {
            clearSelection();
            message.textContent = '選択を取り消しました。';
        } else apply(event.target);
    });
    root.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            clearSelection();
            message.textContent = '選択を取り消しました。';
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            event.target.click();
        }
    });
    input.form.addEventListener('submit', event => {
        input.value = JSON.stringify(data);
        const error = validate(data);
        if (error) {
            event.preventDefault();
            message.textContent = '保存できません：' + error;
        }
    });
})();

(function () {
    'use strict';

    function validate(data, mode) {
        const families = new Map();
        for (const [index, group] of [...data.cars, data.walk].entries()) {
            if (index < data.cars.length) {
                if (!group.length || group.length > 4) return '車の人数は1〜4人にしてください。';
                if (!group.some(p => p.is_driver === '1')) return '各車にドライバーが必要です。';
                if (group.length === 2 && group[0].gender !== group[1].gender && group[0].family_id !== group[1].family_id) {
                    return '家族が異なる男女を2人だけの車にはできません。';
                }
            }
            if (mode === 'family') {
                for (const p of group) {
                    const family = String(p.family_id);
                    if (families.has(family) && families.get(family) !== index) {
                        return '「家族を一緒に」では家族を別の組に分けられません。';
                    }
                    families.set(family, index);
                }
            }
        }
        return '';
    }

    // 候補を複製してから変更する。失敗した操作で現在の配置を壊さない。
    function edit(data, sourceId, targetId, targetGroup, mode) {
        const next = {cars: data.cars.map(g => g.slice()), walk: data.walk.slice()};
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
        if (targetId !== null) {
            const target = locate(targetId);
            if (!target) return {error: 'メンバーが見つかりません。'};
            [source.group[source.index], target.group[target.index]] = [target.group[target.index], source.group[source.index]];
        } else {
            const group = targetGroup === 'walk' ? next.walk : next.cars[Number(targetGroup)];
            if (!group) return {error: '移動先が見つかりません。'};
            if (group !== source.group) group.push(...source.group.splice(source.index, 1));
        }
        const error = validate(next, mode);
        if (error) return {error};
        next.cars.forEach(g => g.sort((a, b) => Number(b.is_driver === '1') - Number(a.is_driver === '1')));
        return {data: next};
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = {validate, edit};
    if (typeof document === 'undefined') return;
    const root = document.getElementById('pairing-editor');
    if (!root) return;
    const input = document.getElementById('pairing-result');
    const message = document.getElementById('pairing-edit-message');
    let data = JSON.parse(input.value);
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
        clearSelection();
        selected = row.dataset.memberId;
        row.classList.add('is-selected');
        row.setAttribute('aria-pressed', 'true');
        message.textContent = '入れ替える名前、または移動先の枠を選んでください。Escで取り消せます。';
    }
    function apply(target) {
        const row = target.closest('[data-member-id]');
        const card = target.closest('[data-group]');
        if (!card || selected === null) return;
        const outcome = edit(data, selected, row ? row.dataset.memberId : null, card.dataset.group, root.dataset.mode);
        clearSelection();
        if (outcome.error) {
            message.textContent = outcome.error + ' 変更を取り消しました。';
            return;
        }
        data = outcome.data;
        root.querySelectorAll('[data-group]').forEach(card => {
            const group = card.dataset.group === 'walk' ? data.walk : data.cars[Number(card.dataset.group)];
            group.forEach(p => card.querySelector('.passenger-list').appendChild(rows.get(String(p.id))));
            card.querySelector('.badge').textContent = group.length + '人';
        });
        input.value = JSON.stringify(data);
        document.getElementById('pairing-score').textContent = '手動調整済み（履歴スコアは未計算）';
        message.textContent = '組合せを変更しました。「この組合せで決定」で保存できます。';
    }
    root.addEventListener('dragstart', event => {
        const row = event.target.closest('[data-member-id]');
        if (!row) return;
        select(row);
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
    input.form.addEventListener('submit', () => { input.value = JSON.stringify(data); });
})();

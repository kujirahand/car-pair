const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {edit, validate} = require('../public/pairing-editor.js');
const person = (id, driver = false, family = `F${id}`, gender = 'M') =>
    ({id: String(id), name: `P${id}`, is_driver: driver ? '1' : '0', family_id: family, gender});
const initial = () => ({cars: [[person(1, true), person(2)], [person(3, true), person(4)]], walk: [person(5)]});
const ids = data => [...data.cars, data.walk].map(g => g.map(p => p.id));

const base = initial();
const swapped = edit(base, '2', '4', '1', 'family');
assert.deepEqual(ids(swapped.data), [['1', '4'], ['3', '2'], ['5']]);
assert.deepEqual(ids(base), [['1', '2'], ['3', '4'], ['5']], '元の配置を変更しない');
assert.deepEqual(ids(edit(base, '2', '5', 'walk', 'family').data), [['1', '5'], ['3', '4'], ['2']]);
assert.deepEqual(ids(edit(base, '2', null, 'walk', 'family').data), [['1'], ['3', '4'], ['5', '2']]);
assert.deepEqual(ids(edit(base, '5', null, '0', 'family').data), [['1', '2', '5'], ['3', '4'], []]);
assert.deepEqual(ids(edit(base, '1', '3', '1', 'family').data), [['3', '2'], ['1', '4'], ['5']]);
assert.match(edit(base, '1', '4', '1', 'family').error, /ドライバー/);
assert.match(edit(base, '1', null, 'walk', 'family').error, /ドライバー/);
const full = initial(); full.cars[0].push(person(6), person(7));
assert.match(edit(full, '5', null, '0', 'family').error, /1〜4/);
const family = initial(); family.cars[0][1].family_id = 'F1';
assert.match(edit(family, '2', '4', '1', 'family').error, /家族/);
assert.match(edit(family, '2', null, 'walk', 'family').error, /家族/);
for (const mode of ['driver', 'random']) assert.ok(edit(family, '2', '4', '1', mode).data);
const mixed = initial(); mixed.walk[0].gender = 'F';
for (const mode of ['family', 'driver', 'random']) assert.match(edit(mixed, '2', '5', 'walk', mode).error, /男女/);
assert.ok(edit(base, '2', '2', '0', 'family').data);
assert.ok(edit(base, '2', null, '0', 'family').data);
assert.match(edit(base, 'missing', '2', '0', 'family').error, /メンバー/);
assert.match(edit(base, '2', 'missing', '0', 'family').error, /メンバー/);
assert.match(edit(base, '2', null, 'missing', 'family').error, /移動先/);
assert.equal(validate({cars: [], walk: [person(1), person(2)]}, 'family'), '');
assert.equal(validate({cars: [[person(1, true), person(2, true)]], walk: []}, 'family'), '');
assert.match(validate({cars: [[]], walk: []}, 'family'), /1〜4/);

// ブラウザを起動せず、実際のイベントハンドラーとフォーム同期を検証する。
class Element {
    constructor(dataset = {}) {
        this.dataset = dataset; this.parent = null; this.children = []; this.attrs = {}; this.textContent = '';
        this.classes = new Set(); this.handlers = {};
        this.classList = {add: name => this.classes.add(name), remove: name => this.classes.delete(name)};
    }
    appendChild(row) {
        if (row.parent) row.parent.children = row.parent.children.filter(p => p !== row);
        this.children.push(row); row.parent = this;
    }
    setAttribute(name, value) { this.attrs[name] = value; }
    addEventListener(name, fn) { this.handlers[name] = fn; }
    closest(selector) {
        if (selector === '[data-member-id]' && this.dataset.memberId !== undefined) return this;
        if (selector === '[data-group]' && this.dataset.group !== undefined) return this;
        return this.parent?.closest(selector) ?? null;
    }
    click() { root.handlers.click({target: this}); }
}
const root = new Element({mode: 'family'});
const rows = new Map();
const cards = [...base.cars, base.walk].map((group, index) => {
    const card = new Element({group: index === base.cars.length ? 'walk' : String(index)});
    const list = new Element(); list.parent = card;
    const badge = new Element();
    card.querySelector = selector => selector === '.badge' ? badge : list;
    group.forEach(p => {const row = new Element({memberId: p.id}); rows.set(p.id, row); list.appendChild(row);});
    return card;
});
root.querySelectorAll = selector => selector === '[data-member-id]' ? [...rows.values()] :
    selector === '[data-group]' ? cards : cards.filter(c => c.classes.has('drop-target'));
const input = {value: JSON.stringify(base), form: new Element()};
const message = new Element(); const score = new Element();
const elements = {'pairing-editor': root, 'pairing-result': input, 'pairing-edit-message': message, 'pairing-score': score};
vm.runInNewContext(fs.readFileSync(require.resolve('../public/pairing-editor.js'), 'utf8'), {
    document: {getElementById: id => elements[id]}
});
const transfer = {setData(type, id) {this.id = id;}};
root.handlers.dragstart({target: rows.get('2'), dataTransfer: transfer});
assert.equal(transfer.id, '2');
let prevented = false;
root.handlers.dragover({target: rows.get('4'), dataTransfer: transfer, preventDefault() {prevented = true;}});
assert.ok(prevented);
assert.ok(cards[1].classes.has('drop-target'));
root.handlers.drop({target: rows.get('4'), preventDefault() {}});
root.handlers.dragend();
assert.deepEqual(ids(JSON.parse(input.value)), [['1', '4'], ['3', '2'], ['5']]);
assert.deepEqual(cards[0].querySelector('.passenger-list').children.map(p => p.dataset.memberId), ['1', '4']);
assert.equal(cards[0].querySelector('.badge').textContent, '2人');
assert.match(score.textContent, /手動調整済み/);
assert.match(message.textContent, /変更しました/);
const saved = input.value;
rows.get('1').click(); rows.get('2').click();
assert.equal(input.value, saved, '不正な変更では送信内容も画面も変更しない');
assert.match(message.textContent, /ドライバー/);
rows.get('4').click(); cards[2].click();
assert.deepEqual(ids(JSON.parse(input.value)), [['1'], ['3', '2'], ['5', '4']]);
assert.equal(cards[2].querySelector('.badge').textContent, '2人');
root.handlers.keydown({key: 'Enter', target: rows.get('4'), preventDefault() {}});
root.handlers.keydown({key: ' ', target: cards[0], preventDefault() {}});
assert.deepEqual(ids(JSON.parse(input.value)), [['1', '4'], ['3', '2'], ['5']]);
rows.get('4').click(); root.handlers.keydown({key: 'Escape'});
assert.equal(rows.get('4').attrs['aria-pressed'], 'false');
rows.get('4').click(); rows.get('4').click();
assert.match(message.textContent, /取り消しました/);
input.value = '{}'; input.form.handlers.submit();
assert.deepEqual(ids(JSON.parse(input.value)), [['1', '4'], ['3', '2'], ['5']]);
console.log('JavaScript: 編集条件、Drag&Drop、タップ、キーボード、表示・フォーム同期 OK');

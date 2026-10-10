const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {edit, validate, carLabel} = require('../public/pairing-editor.js');
const person = (id, driver = false, family = `F${id}`, gender = 'M') =>
    ({id: String(id), name: `P${id}`, is_driver: driver ? '1' : '0', family_id: family, gender});
const initial = () => ({cars: [[person(1, true), person(2)], [person(3, true), person(4)]], walk: [person(5)]});
const ids = data => [...data.cars, data.walk].map(g => g.map(p => p.id));
const base = initial();
assert.deepEqual(ids(edit(base, '2', '1').data), [['1'], ['3', '4', '2'], ['5']], '相手を戻さず移動する');
assert.deepEqual(ids(base), [['1', '2'], ['3', '4'], ['5']], '元の配置を変更しない');
assert.deepEqual(ids(edit(base, '2', 'walk').data), [['1'], ['3', '4'], ['5', '2']]);
assert.deepEqual(ids(edit(base, '5', '0').data), [['1', '2', '5'], ['3', '4'], []]);
assert.match(validate(edit(base, '1', '1').data), /ドライバー/, '編集中はドライバー不在でも移せる');
const full = initial(); full.cars[0].push(person(6), person(7));
assert.equal(edit(full, '5', '0').data.cars[0].length, 5);
assert.equal(validate(edit(full, '5', '0').data), '', '手動調整では5人以上でも保存できる');
const locked = initial(); locked.locked = ['0'];
assert.match(edit(locked, '2', '1').error, /固定/);
assert.match(edit(locked, '4', '0').error, /固定/);
assert.deepEqual(edit(locked, '4', 'walk').data.locked, ['0']);
const family = initial(); family.cars[0][1].family_id = 'F1';
assert.ok(edit(family, '2', '1').data); assert.ok(edit(family, '2', 'walk').data);
assert.deepEqual(ids(edit(base, '2', '0').data), ids(base));
assert.match(edit(base, 'missing', '0').error, /メンバー/);
assert.match(edit(base, '2', 'missing').error, /移動先/);
assert.equal(validate({cars: [[], []], walk: [person(1), person(2)]}), '');
assert.equal(validate({cars: [[person(1, true), person(2, true)]], walk: []}), '');
const draft = initial(); draft.cars.push([]);
const passengerFirst = edit(draft, '2', '2').data;
assert.match(validate(passengerFirst), /ドライバー/);
const completed = edit(passengerFirst, '1', '2').data;
assert.deepEqual(ids(completed), [[], ['3', '4'], ['1', '2'], ['5']]);
assert.equal(validate(completed), '');
const mixed = {cars: [[person(1, true)], []], walk: [person(2, false, 'F2', 'F'), person(3)]};
const mixedPair = edit(mixed, '2', '0').data;
assert.match(validate(mixedPair), /男女/);
assert.equal(validate(edit(mixedPair, '3', '0').data), '', '中間状態を経て3人にできる');
for (const [index, label] of [[0, '車A'], [1, '車B'], [25, '車Z'], [26, '車AA'], [27, '車AB'], [701, '車ZZ'], [702, '車AAA']]) assert.equal(carLabel(index), label);

// ブラウザを起動せず実際のイベントハンドラーとフォーム同期を検証する。
class Element {
    constructor(dataset = {}) {
        this.dataset = dataset; this.parent = null; this.children = []; this.attrs = {}; this.textContent = ''; this.className = '';
        this.classes = new Set(); this.handlers = {};
        this.classList = {add: name => this.classes.add(name), remove: name => this.classes.delete(name)};
    }
    appendChild(row) {
        if (row.parent) row.parent.children = row.parent.children.filter(p => p !== row);
        this.children.push(row); row.parent = this;
    }
    insertBefore(row, before) { row.parent = this; this.children.splice(this.children.indexOf(before), 0, row); }
    matches(selector) {
        if (selector === '[data-member-id]') return this.dataset.memberId !== undefined;
        if (selector === '[data-group]') return this.dataset.group !== undefined;
        if (selector === '[data-group="walk"]') return this.dataset.group === 'walk';
        if (selector.startsWith('.')) return this.classes.has(selector.slice(1)) || this.className.split(' ').includes(selector.slice(1));
        return false;
    }
    querySelectorAll(selector) { return this.children.flatMap(child => [...(child.matches(selector) ? [child] : []), ...child.querySelectorAll(selector)]); }
    querySelector(selector) { return this.querySelectorAll(selector)[0] ?? null; }
    setAttribute(name, value) { this.attrs[name] = value; }
    addEventListener(name, fn) { this.handlers[name] = fn; }
    closest(selector) { return this.matches(selector) ? this : this.parent?.closest(selector) ?? null; }
    click() { (this.handlers.click ?? root.handlers.click)({target: this}); }
    focus() { this.focused = true; }
}
const root = new Element(); const rows = new Map();
const cards = [...base.cars, base.walk].map((group, index) => {
    const card = new Element({group: index === base.cars.length ? 'walk' : String(index)});
    const list = new Element(); list.className = 'passenger-list';
    const badge = new Element(); badge.className = 'badge';
    card.appendChild(badge); card.appendChild(list); root.appendChild(card);
    group.forEach(p => {
        const row = new Element({memberId: p.id});
        const familyBadge = new Element(); familyBadge.className = 'family-initial';
        row.appendChild(familyBadge); rows.set(p.id, row); list.appendChild(row);
    });
    return card;
});
const input = {value: JSON.stringify(base), form: new Element()};
const message = new Element(); const score = new Element(); const add = new Element();
const elements = {'pairing-editor': root, 'pairing-result': input, 'pairing-edit-message': message, 'pairing-score': score, 'add-pairing-group': add};
vm.runInNewContext(fs.readFileSync(require.resolve('../public/pairing-editor.js'), 'utf8'), {
    document: {getElementById: id => elements[id], createElement: () => new Element()}
});
const transfer = {setData(type, id) {this.id = id;}};
root.handlers.dragstart({target: rows.get('2'), dataTransfer: transfer}); assert.equal(transfer.id, '2');
let prevented = false;
root.handlers.dragover({target: rows.get('4'), dataTransfer: transfer, preventDefault() {prevented = true;}});
assert.ok(prevented); assert.ok(cards[1].classes.has('drop-target'));
const badgeBefore = rows.get('2').querySelector('.family-initial');
root.handlers.drop({target: rows.get('4'), preventDefault() {}}); root.handlers.dragend();
assert.deepEqual(ids(JSON.parse(input.value)), [['1'], ['3', '4', '2'], ['5']]);
assert.deepEqual(cards[1].querySelector('.passenger-list').children.map(p => p.dataset.memberId), ['3', '4', '2']);
assert.equal(rows.get('2').querySelector('.family-initial'), badgeBefore);
assert.equal(cards[0].querySelector('.badge').textContent, '1人'); assert.equal(cards[1].querySelector('.badge').textContent, '3人');
assert.match(score.textContent, /手動調整済み/); assert.match(message.textContent, /変更しました/);
rows.get('2').click(); add.click();
const newCard = root.querySelectorAll('[data-group]')[2];
assert.equal(newCard.dataset.group, '2'); assert.equal(newCard.querySelector('.badge').textContent, '0人');
assert.equal(newCard.children[0].children[0].textContent, '🚗 車C'); assert.equal(newCard.attrs['aria-label'], '車Cに移動'); assert.ok(newCard.focused);
newCard.click();
assert.deepEqual(ids(JSON.parse(input.value)), [['1'], ['3', '4'], ['2'], ['5']]); assert.match(message.textContent, /決定前.*ドライバー/);
let blocked = false;
input.form.handlers.submit({preventDefault() {blocked = true;}}); assert.ok(blocked); assert.match(message.textContent, /保存できません/);
root.handlers.keydown({key: 'Enter', target: rows.get('1'), preventDefault() {}});
root.handlers.keydown({key: ' ', target: newCard, preventDefault() {}});
assert.deepEqual(ids(JSON.parse(input.value)), [[], ['3', '4'], ['1', '2'], ['5']]);
blocked = false; input.form.handlers.submit({preventDefault() {blocked = true;}}); assert.equal(blocked, false);
assert.equal(cards[0].querySelector('.badge').textContent, '0人');
add.click(); add.click(); assert.equal(root.querySelectorAll('[data-group]')[4].children[0].children[0].textContent, '🚗 車E');
rows.get('4').click(); cards[2].click();
assert.deepEqual(ids(JSON.parse(input.value)), [[], ['3'], ['1', '2'], [], [], ['5', '4']]);
rows.get('4').click(); root.handlers.keydown({key: 'Escape'}); assert.equal(rows.get('4').attrs['aria-pressed'], 'false');
rows.get('4').click(); rows.get('4').click(); assert.match(message.textContent, /取り消しました/);
input.value = '{}'; input.form.handlers.submit({preventDefault() {throw Error('有効な配置');}});
assert.deepEqual(ids(JSON.parse(input.value)), [[], ['3'], ['1', '2'], [], [], ['5', '4']]);
root.handlers.dragstart({target: rows.get('1'), dataTransfer: transfer});
root.handlers.drop({target: rows.get('2'), preventDefault() {}}); root.handlers.dragend();
assert.deepEqual(ids(JSON.parse(input.value)), [[], ['3'], ['1', '2'], [], [], ['5', '4']], '同じ組でも入れ替えない');
console.log('JavaScript: 移動、空グループ追加、家族分離、ABC表示、保存検証、Drag&Drop・タップ・キーボード・表示同期 OK');

// 新規グループの固定ボタン・解除・フォーム送信での固定状態を確認。
const lockButton = newCard.querySelector('.group-lock');
lockButton.click();
assert.deepEqual(JSON.parse(input.value).locked, ['2']);
assert.equal(lockButton.textContent, '固定解除');
assert.equal(rows.get('1').attrs.draggable, 'false');
rows.get('1').click();
assert.match(message.textContent, /固定中/);
rows.get('3').click();
let lockedDragBlocked = false;
root.handlers.dragstart({target: rows.get('1'), dataTransfer: transfer, preventDefault() {lockedDragBlocked = true;}});
assert.ok(lockedDragBlocked, '固定されたメンバーのドラッグを防ぐ');
const beforeLockedMove = input.value;
rows.get('3').click(); newCard.click();
assert.equal(input.value, beforeLockedMove);
assert.match(message.textContent, /固定/);
input.form.handlers.submit({preventDefault() {throw Error('固定した候補は送信できる');}});
assert.deepEqual(JSON.parse(input.value).locked, ['2']);
lockButton.click();
assert.deepEqual(JSON.parse(input.value).locked, []);
assert.equal(lockButton.textContent, '決定');
assert.equal(rows.get('1').attrs.draggable, 'true');

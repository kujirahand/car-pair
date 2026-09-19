<?php
// ハンバーガーメニューに出す、この画面専用の項目(layout.php が表示する)
$pageMenuItems = [
    ['label' => '📝 テキストから追加', 'href' => '?action=select_by_textbox'],
    ['label' => '🗑️ 全部クリア', 'id' => 'clear-all-btn', 'danger' => true],
];
?>
<div class="page-header">
    <h1 class="page-title">👥 今回の参加者を選択</h1>
</div>
<style>
.table tbody tr.selected-row {
    background-color: #eff6ff !important;
}
.table tbody tr.selected-row td {
    border-bottom-color: #bfdbfe;
}

.mode-select {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-weight: 500;
    color: var(--text-muted);
}
.mode-select select {
    width: auto;
    margin-bottom: 0;
}

.table tbody tr.section-row td {
    background: #f1f5f9;
    font-weight: 700;
    padding: 0.6rem 1rem;
    border-bottom: 1px solid var(--border);
}
#selected-body tr.section-row td {
    background: #dbeafe;
    color: #1e40af;
}
/* 選択中メンバーの表示/非表示スイッチ(非表示時は見出しだけ残す) */
#selected-body.is-hidden tr:not(.section-row) {
    display: none !important;
}
.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    vertical-align: middle;
    font-weight: 400;
    cursor: pointer;
    user-select: none;
    font-size: 0.85rem;
}
.selected-toggle {
    margin-left: 0.75rem;
}
.toggle-switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-switch .switch-track {
    position: relative;
    width: 38px;
    height: 22px;
    border-radius: 11px;
    background: #cbd5e1;
    transition: background 0.15s;
}
.toggle-switch .switch-track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    transition: transform 0.15s;
}
.toggle-switch input:checked + .switch-track {
    background: var(--primary-color, #2563eb);
}
.toggle-switch input:checked + .switch-track::after {
    transform: translateX(16px);
}
.toggle-switch input:focus-visible + .switch-track {
    outline: 2px solid var(--primary-color, #2563eb);
    outline-offset: 2px;
}
/* ふりがなは検索専用(表示しない) */
.table td.furigana-cell {
    display: none !important;
}
.table tbody tr.empty-row td {
    color: var(--text-muted);
    text-align: center;
    padding: 1rem;
}

/* Mobile: 1人1行のコンパクト表示(ドライバー/乗客だけタップで切替) */
@media (max-width: 768px) {
    .table-responsive table,
    .table-responsive thead,
    .table-responsive tbody,
    .table-responsive th,
    .table-responsive td,
    .table-responsive tr {
        display: block;
    }

    /* ヘッダーは隠す(display: none にはしない) */
    .table-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    .table-responsive tr {
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        margin-bottom: 0.35rem;
        background: #fff;
    }

    .table-responsive td {
        border: none;
        padding: 0;
    }

    /* メンバー行: [チェック] 名前 ........ [タイプ] を1行に */
    .table-responsive tr.member-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.3rem 0.6rem;
        min-height: 44px;
    }
    .table-responsive tr.member-row td.checkbox-cell {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
    }
    .table-responsive tr.member-row td.checkbox-cell input {
        width: 22px;
        height: 22px;
    }
    .table-responsive tr.member-row td.name-cell {
        flex: 1 1 auto;
        min-width: 0;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .table-responsive tr.member-row td.type-cell {
        flex: 0 0 auto;
    }

    /* タイプはスイッチではなくチップ全体をタップして切替 */
    .table-responsive .type-cell .toggle-switch {
        gap: 0;
    }
    .table-responsive .type-cell .switch-track {
        display: none;
    }
    .table-responsive .type-cell .driver-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 5rem;
        min-height: 34px;
        padding: 0 0.6rem;
        border-radius: 17px;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table-responsive .type-cell .driver-label.badge-driver {
        border: 1px solid #86efac;
    }
    .table-responsive .type-cell .driver-label.badge-passenger {
        border: 1px solid var(--border);
    }

    .table-responsive tr.section-row,
    .table-responsive tr.empty-row {
        border: none;
        background: transparent;
        margin-bottom: 0.35rem;
    }
    .table-responsive tr.section-row td,
    .table-responsive tr.empty-row td {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
        text-align: left;
    }

    /* 名前とタイプ以外は非表示 */
    .table-responsive td.family-cell,
    .table-responsive td.nickname-cell,
    .table-responsive td.notes-cell,
    .table-responsive td.count-val {
        display: none;
    }

    .mode-select span {
        display: none;
    }
    .sticky-actions .mode-select select {
        min-width: 0;
        max-width: 9.5rem;
        padding: 0.5rem 0.6rem;
    }
}
</style>

<div class="card form-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="?action=select_members" method="post" id="select-members-form">
        <div class="mb-3" style="display: flex; gap: 8px; align-items: center; margin-bottom: 0.6rem;">
            <input type="search" id="member-search" class="form-control" placeholder="名前、かなの一部から検索" style="flex: 1; margin-bottom: 0;">
            <button type="button" onclick="clearSearch()" style="flex-shrink: 0; padding: 0.5rem 0.9rem; border: 1px solid var(--border); border-radius: var(--radius); background: #fff; color: var(--text-muted); font-size: 1rem; cursor: pointer; line-height: 1; transition: background 0.15s, color 0.15s;" onmouseover="this.style.background='#f3f4f6';this.style.color='var(--text)'" onmouseout="this.style.background='#fff';this.style.color='var(--text-muted)'">×</button>
        </div>
        
        <div class="table-responsive">
            <table class="table hover-table">
                <thead>
                    <tr>
                        <th width="50" class="text-center">
                            <input type="checkbox" id="check-all" title="すべて選択">
                        </th>
                        <th class="sortable" data-sort="name" style="cursor: pointer; user-select: none;" title="クリックでソート">名前 <span class="sort-icon text-muted" style="font-size: 0.8em; margin-left: 4px;">↕</span></th>
                        <th>家族ID</th>
                        <th>タイプ</th>
                        <th class="sortable" data-sort="nickname" style="cursor: pointer; user-select: none;" title="クリックでソート">ニックネーム <span class="sort-icon text-muted" style="font-size: 0.8em; margin-left: 4px;">↕</span></th>
                        <th>備考</th>
                        <th class="sortable" data-sort="count" style="cursor: pointer; user-select: none;" title="クリックでソート">参加回数 <span class="sort-icon text-muted" style="font-size: 0.8em; margin-left: 4px;">↓</span></th>
                    </tr>
                </thead>
                <tbody id="selected-body">
                    <tr class="section-row"><td colspan="7">✅ 選択中 <span id="selected-heading-count">0</span> 人
                        <label class="toggle-switch selected-toggle" title="選択中のメンバーの表示/非表示">
                            <input type="checkbox" id="toggle-selected" checked role="switch">
                            <span class="switch-track"></span>
                            <span id="toggle-selected-label">表示</span>
                        </label>
                    </td></tr>
                    <tr class="empty-row" id="selected-empty"><td colspan="7">まだ選ばれていません。下の候補から選んでください。</td></tr>
                </tbody>
                <tbody id="candidate-body">
                    <tr class="section-row"><td colspan="7">➕ 選択候補 <span id="candidate-heading-count">0</span> 人</td></tr>
                    <tr class="empty-row" id="candidate-empty" style="display: none;"><td colspan="7"><?= empty($members) ? '名簿がありません。「名簿編集」から登録してください。' : '選択候補はありません。' ?></td></tr>
                    <?php foreach ($members as $m): ?>
                    <tr class="member-row">
                        <td class="text-center checkbox-cell" data-label="選択">
                            <input type="checkbox" name="selected_ids[]" value="<?= htmlspecialchars($m['id']) ?>" class="member-checkbox" <?= in_array($m['id'], $selectedIds) ? 'checked' : '' ?>>
                        </td>
                        <td class="name-cell" data-label="名前">
                            <span class="member-name <?= $m['gender'] === 'M' ? 'man' : 'woman' ?>"><?= htmlspecialchars($m['name']) ?></span>
                        </td>
                        <td class="furigana-cell" data-label="ふりがな"><?= htmlspecialchars($m['furigana'] ?? '') ?></td>
                        <td class="family-cell" data-label="家族ID"><span class="family-tag"><?= htmlspecialchars($m['family_id']) ?></span></td>
                        <td class="type-cell" data-label="タイプ">
                            <label class="toggle-switch" onclick="event.stopPropagation();">
                                <input type="checkbox" name="is_driver[<?= htmlspecialchars($m['id']) ?>]" value="1" <?= $m['is_driver'] === '1' ? 'checked' : '' ?> class="driver-checkbox" role="switch">
                                <span class="switch-track"></span>
                                <span class="driver-label <?= $m['is_driver'] === '1' ? 'badge-driver' : 'badge-passenger' ?>">
                                    <?= $m['is_driver'] === '1' ? '🚗 運転' : '👤 乗客' ?>
                                </span>
                            </label>
                        </td>
                        <td class="nickname-cell" data-label="ニックネーム"><?= htmlspecialchars($m['nickname'] ?? '') ?></td>
                        <td class="notes-cell" data-label="備考"><?= htmlspecialchars($m['notes'] ?? '') ?></td>
                        <td class="count-val" data-label="参加回数"><strong><?= htmlspecialchars($m['participation_count']) ?></strong> 回</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="form-actions sticky-actions">
            <div class="selection-summary">
                <span id="selected-count" class="badge">0</span> 人
            </div>
            <label class="mode-select" for="pairing-mode">
                <span>組み方</span>
                <select name="pairing_mode" id="pairing-mode" class="form-control">
                    <?php foreach ($pairingModes as $modeId => $modeName): ?>
                    <option value="<?= htmlspecialchars($modeId) ?>" <?= $modeId === $pairingMode ? 'selected' : '' ?>><?= htmlspecialchars($modeName) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary pulse-hover">選択完了</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkAll = document.getElementById('check-all');
    const checkboxes = document.querySelectorAll('.member-checkbox');
    const countSpan = document.getElementById('selected-count');
    const search = document.getElementById('member-search');
    const selectedBody = document.getElementById('selected-body');
    const candidateBody = document.getElementById('candidate-body');
    const selectedEmpty = document.getElementById('selected-empty');
    const candidateEmpty = document.getElementById('candidate-empty');
    const selectedHeading = document.getElementById('selected-heading-count');
    const candidateHeading = document.getElementById('candidate-heading-count');

    let currentSort = 'count';
    let currentDir = -1; // -1: 降順, 1: 昇順

    const text = (row, sel) => row.querySelector(sel).textContent.trim();
    const compareRows = (a, b) => {
        if (currentSort === 'name') {
            return text(a, '.member-name').localeCompare(text(b, '.member-name'), 'ja') * currentDir;
        } else if (currentSort === 'nickname') {
            return text(a, '.nickname-cell').localeCompare(text(b, '.nickname-cell'), 'ja') * currentDir;
        } else if (currentSort === 'count') {
            const valA = parseInt(text(a, '.count-val strong'), 10) || 0;
            const valB = parseInt(text(b, '.count-val strong'), 10) || 0;
            if (valA === valB) {
                return text(a, '.name-cell').localeCompare(text(b, '.name-cell'), 'ja');
            }
            return (valA - valB) * currentDir;
        }
        return 0;
    };

    const sortBody = (body) => {
        const rows = Array.from(body.querySelectorAll('tr.member-row'));
        rows.sort(compareRows);
        rows.forEach(row => body.appendChild(row));
    };

    // チェック状態に応じて「選択中」「選択候補」のどちらかに行を振り分ける
    const placeRows = () => {
        checkboxes.forEach(cb => {
            const tr = cb.closest('tr');
            const target = cb.checked ? selectedBody : candidateBody;
            tr.classList.toggle('selected-row', cb.checked);
            if (tr.parentNode !== target) {
                target.appendChild(tr);
            }
        });
        sortBody(selectedBody);
        sortBody(candidateBody);
    };

    const updateCount = () => {
        const count = Array.from(checkboxes).filter(cb => cb.checked).length;
        const candidates = checkboxes.length - count;
        countSpan.textContent = count;
        countSpan.classList.toggle('active-count', count > 0);
        selectedHeading.textContent = count;
        candidateHeading.textContent = candidates;
        selectedEmpty.style.display = count === 0 ? '' : 'none';
        candidateEmpty.style.display = candidates === 0 ? '' : 'none';
        if (checkAll) checkAll.checked = checkboxes.length > 0 && count === checkboxes.length;
    };

    // 検索フィルター(選択中の人は常に表示し、候補だけを絞り込む)
    const applyFilter = () => {
        const query = search.value.trim().toLowerCase();
        candidateBody.querySelectorAll('tr.member-row').forEach(row => {
            const fields = ['.name-cell', '.furigana-cell', '.family-tag', '.nickname-cell', '.notes-cell'];
            const hit = fields.some(sel => row.querySelector(sel).textContent.toLowerCase().includes(query));
            row.style.display = hit ? '' : 'none';
        });
    };

    const refresh = () => {
        placeRows();
        updateCount();
        applyFilter();
    };

    // 選択中メンバーの表示/非表示スイッチ
    const toggleSelected = document.getElementById('toggle-selected');
    const toggleLabel = document.getElementById('toggle-selected-label');
    const applySelectedVisibility = () => {
        selectedBody.classList.toggle('is-hidden', !toggleSelected.checked);
        toggleLabel.textContent = toggleSelected.checked ? '表示' : '非表示';
    };
    toggleSelected.addEventListener('change', applySelectedVisibility);
    applySelectedVisibility();

    window.clearSearch = () => {
        search.value = '';
        applyFilter();
    };

    refresh(); // 初期表示

    if (checkAll) {
        checkAll.addEventListener('change', (e) => {
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            refresh();
        });
    }

    const clearAllBtn = document.getElementById('clear-all-btn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            if (confirm('全ての選択をクリアしますか？')) {
                checkboxes.forEach(cb => cb.checked = false);
                refresh();
            }
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', refresh);
        cb.closest('tr').addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL' && !e.target.closest('label')) {
                cb.checked = !cb.checked;
                refresh();
            }
        });
    });

    search.addEventListener('input', applyFilter);

    // ドライバー切り替え
    document.querySelectorAll('.driver-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const badge = this.closest('label').querySelector('.driver-label');
            if (this.checked) {
                badge.className = 'driver-label badge-driver';
                badge.innerHTML = '🚗 運転';
            } else {
                badge.className = 'driver-label badge-passenger';
                badge.innerHTML = '👤 乗客';
            }
        });
    });

    // ソート機能
    const sortHeaders = document.querySelectorAll('.sortable');
    sortHeaders.forEach(th => {
        th.addEventListener('click', () => {
            const sortType = th.getAttribute('data-sort');
            if (currentSort === sortType) {
                currentDir *= -1;
            } else {
                currentSort = sortType;
                currentDir = sortType === 'count' ? -1 : 1; // 回数は降順、名前は昇順がデフォルト
            }
            sortHeaders.forEach(header => {
                const icon = header.querySelector('.sort-icon');
                if (header.getAttribute('data-sort') === currentSort) {
                    icon.textContent = currentDir === 1 ? '↑' : '↓';
                } else {
                    icon.textContent = '↕';
                }
            });
            sortBody(selectedBody);
            sortBody(candidateBody);
        });
    });
});
</script>

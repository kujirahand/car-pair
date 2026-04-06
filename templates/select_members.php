<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <h1 class="page-title" style="margin-bottom: 0;">👥 今回の参加者を選択</h1>
    <div style="display: flex; gap: 10px;">
        <a href="?action=select_by_textbox" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary); background: #fff;">📝 テキストから追加</a>
        <a href="?action=select_by_screenshot" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary); background: #fff;">📸 スクショから選択</a>
        <button type="button" id="clear-all-btn" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger); background: #fff;">🗑️ 全部クリア</button>
    </div>
</div>
<style>
.table tbody tr.selected-row {
    background-color: #eff6ff !important;
}
.table tbody tr.selected-row td {
    border-bottom-color: #bfdbfe;
}

/* Mobile responsive table */
@media (max-width: 768px) {
    .table-responsive table,
    .table-responsive thead,
    .table-responsive tbody,
    .table-responsive th,
    .table-responsive td,
    .table-responsive tr {
        display: block;
    }

    /* Hide table headers (but not display: none;, for accessibility) */
    .table-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    .table-responsive tr {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 1rem;
        background: #fff;
        padding: 0.5rem;
        box-shadow: var(--shadow-sm);
    }

    .table-responsive td {
        border: none;
        border-bottom: 1px solid #f3f4f6;
        position: relative;
        padding-left: 35%;
        text-align: right;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .table-responsive td:last-child {
        border-bottom: 0;
    }

    /* Column titles for mobile */
    .table-responsive td::before {
        content: attr(data-label);
        position: absolute;
        left: 1rem;
        width: 30%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    /* Special styling for checkbox row on mobile */
    .table-responsive td.checkbox-cell {
        padding-left: 1rem;
        justify-content: flex-start;
        background: #f8fafc;
        border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        margin: -0.5rem -0.5rem 0.5rem -0.5rem;
        border-bottom: 1px solid var(--border);
    }
    
    .table-responsive td.checkbox-cell::before {
        display: none;
    }

    /* Simple mobile view: Only show Name and Type */
    .table-responsive td[data-label="ふりがな"],
    .table-responsive td[data-label="家族ID"],
    .table-responsive td[data-label="ニックネーム"],
    .table-responsive td[data-label="備考"],
    .table-responsive td[data-label="参加回数"] {
        display: none;
    }
}
</style>

<div class="card form-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="?action=select_members" method="post" id="select-members-form">
        <div class="mb-3" style="display: flex; gap: 8px; align-items: center;">
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
                        <th class="sortable" data-sort="furigana" style="cursor: pointer; user-select: none;" title="クリックでソート">ふりがな <span class="sort-icon text-muted" style="font-size: 0.8em; margin-left: 4px;">↕</span></th>
                        <th>家族ID</th>
                        <th>タイプ</th>
                        <th>ニックネーム</th>
                        <th>備考</th>
                        <th class="sortable" data-sort="count" style="cursor: pointer; user-select: none;" title="クリックでソート">参加回数 <span class="sort-icon text-muted" style="font-size: 0.8em; margin-left: 4px;">↓</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">名簿がありません。「名簿編集」から登録してください。</td></tr>
                    <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td class="text-center checkbox-cell" data-label="選択">
                            <input type="checkbox" name="selected_ids[]" value="<?= htmlspecialchars($m['id']) ?>" class="member-checkbox" <?= in_array($m['id'], $selectedIds) ? 'checked' : '' ?>>
                        </td>
                        <td class="name-cell" data-label="名前">
                            <span class="member-name"><?= htmlspecialchars($m['name']) ?></span>
                            <span class="badge-gender-<?= strtolower($m['gender']) ?>">
                                <?= $m['gender'] === 'M' ? '男' : '女' ?>
                            </span>
                        </td>
                        <td class="furigana-cell" data-label="ふりがな"><?= htmlspecialchars($m['furigana'] ?? '') ?></td>
                        <td data-label="家族ID"><span class="family-tag"><?= htmlspecialchars($m['family_id']) ?></span></td>
                        <td data-label="タイプ">
                            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" onclick="event.stopPropagation();">
                                <input type="checkbox" name="is_driver[<?= htmlspecialchars($m['id']) ?>]" value="1" <?= $m['is_driver'] === '1' ? 'checked' : '' ?> class="driver-checkbox">
                                <span class="<?= $m['is_driver'] === '1' ? 'badge-driver' : 'badge-passenger' ?>">
                                    <?= $m['is_driver'] === '1' ? '🚗 ドライバー' : '👤 乗客' ?>
                                </span>
                            </label>
                        </td>
                        <td class="nickname-cell" data-label="ニックネーム"><?= htmlspecialchars($m['nickname'] ?? '') ?></td>
                        <td class="notes-cell" data-label="備考"><?= htmlspecialchars($m['notes'] ?? '') ?></td>
                        <td class="count-val" data-label="参加回数"><strong><?= htmlspecialchars($m['participation_count']) ?></strong> 回</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="form-actions sticky-actions">
            <div class="selection-summary">
                <span id="selected-count" class="badge">0</span> 人
            </div>
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

    const clearSearch = () => {
        search.value = '';
    };

    const updateCount = () => {
        const count = Array.from(checkboxes).filter(cb => {
            const tr = cb.closest('tr');
            if (cb.checked) {
                tr.classList.add('selected-row');
            } else {
                tr.classList.remove('selected-row');
            }
            return cb.checked;
        }).length;
        countSpan.textContent = count;
        countSpan.classList.toggle('active-count', count > 0);
    };

    updateCount(); // Initialize state on load

    if (checkAll) {
        checkAll.addEventListener('change', (e) => {
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateCount();
        });
    }

    const clearAllBtn = document.getElementById('clear-all-btn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            if (confirm('全ての選択をクリアしますか？')) {
                checkboxes.forEach(cb => cb.checked = false);
                if (checkAll) checkAll.checked = false;
                updateCount();
            }
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
        // Also toggle row selection style class
        cb.closest('tr').addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                cb.checked = !cb.checked;
                updateCount();
            }
        });
    });

    const tableBody = document.querySelector('.hover-table tbody');

    // 検索フィルター機能
    const searchInput = document.getElementById('member-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const td = row.querySelector('td');
                if (!td || td.colSpan > 1) return; // 空の場合のメッセージ行などはスキップ

                const name = row.querySelector('.name-cell').textContent.toLowerCase();
                const furigana = row.querySelector('.furigana-cell').textContent.toLowerCase();
                const familyId = row.querySelector('.family-tag').textContent.toLowerCase();
                const nickname = row.querySelector('.nickname-cell').textContent.toLowerCase();
                const notes = row.querySelector('.notes-cell').textContent.toLowerCase();

                if (name.includes(query) || furigana.includes(query) || familyId.includes(query) || nickname.includes(query) || notes.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // ドライバー切り替え
    document.querySelectorAll('.driver-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const badge = this.nextElementSibling;
            if (this.checked) {
                badge.className = 'badge-driver';
                badge.innerHTML = '🚗 ドライバー';
            } else {
                badge.className = 'badge-passenger';
                badge.innerHTML = '👤 乗客';
            }
        });
    });

    // ソート機能
    const sortHeaders = document.querySelectorAll('.sortable');
    let currentSort = 'count';
    let currentDir = -1; // -1: 降順, 1: 昇順

    sortHeaders.forEach(th => {
        th.addEventListener('click', () => {
            const sortType = th.getAttribute('data-sort');
            if (currentSort === sortType) {
                currentDir *= -1; // 順序を反転
            } else {
                currentSort = sortType;
                currentDir = sortType === 'count' ? -1 : 1; // 回数は降順、名前は昇順がデフォルト
            }

            // アイコンの更新
            sortHeaders.forEach(header => {
                const icon = header.querySelector('.sort-icon');
                if (header.getAttribute('data-sort') === currentSort) {
                    icon.textContent = currentDir === 1 ? '↑' : '↓';
                } else {
                    icon.textContent = '↕';
                }
            });

            // 行のソート
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            if (rows.length === 0 || rows[0].querySelector('td').colSpan > 1) return; // 空のデータ表示時はスキップ

            rows.sort((a, b) => {
                let valA, valB;
                if (currentSort === 'name') {
                    valA = a.querySelector('.member-name').textContent.trim();
                    valB = b.querySelector('.member-name').textContent.trim();
                    return valA.localeCompare(valB, 'ja') * currentDir;
                } else if (currentSort === 'furigana') {
                    valA = a.querySelector('.furigana-cell').textContent.trim();
                    valB = b.querySelector('.furigana-cell').textContent.trim();
                    return valA.localeCompare(valB, 'ja') * currentDir;
                } else if (currentSort === 'count') {
                    valA = parseInt(a.querySelector('.count-val strong').textContent, 10) || 0;
                    valB = parseInt(b.querySelector('.count-val strong').textContent, 10) || 0;
                    if (valA === valB) {
                        const nameA = a.querySelector('.name-cell').textContent.trim();
                        const nameB = b.querySelector('.name-cell').textContent.trim();
                        return nameA.localeCompare(nameB, 'ja');
                    }
                    return (valA - valB) * currentDir;
                }
                return 0;
            });

            // DOMへ再配置
            rows.forEach(row => tableBody.appendChild(row));
        });
    });
});
</script>

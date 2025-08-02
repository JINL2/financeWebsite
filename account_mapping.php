<?php
/**
 * 재무관리 시스템 - 계정 매핑
 */
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// 인증 확인
$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];

// 사용자 정보
$user = getCurrentUser($user_id);
$companies = getUserCompanies($user_id);

// 데이터베이스 연결
$db = new SupabaseDB();

try {
    // 거래처 목록 조회
    $counterparties = $db->getMany('counterparties', [
        'company_id' => $company_id
    ], 'name');
    
    // 계정 목록 조회 (채권/채무 관련)
    $accounts = $db->getMany('accounts', [
        'company_id' => $company_id
    ], 'account_name');
    
    // 채권 계정과 채무 계정 분리
    $receivableAccounts = [];
    $payableAccounts = [];
    
    foreach ($accounts as $account) {
        if (in_array($account['account_name'], ['매출채권', '받을어음', '미수금'])) {
            $receivableAccounts[] = $account;
        } elseif (in_array($account['account_name'], ['매입채무', '지급어음', '미지급금', '미지급급여', '미지급배당금'])) {
            $payableAccounts[] = $account;
        }
    }
    
    // 기존 매핑 조회
    $mappings = $db->getMany('account_mapping', [
        'company_id' => $company_id
    ]);
    
    // 거래처별로 매핑 정리
    $mappingsByCounterparty = [];
    foreach ($mappings as $mapping) {
        $mappingsByCounterparty[$mapping['counterparty_id']] = $mapping;
    }
    
} catch (Exception $e) {
    $error = '데이터를 불러오는데 실패했습니다: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>계정 매핑 - <?= SYSTEM_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 네비게이션 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">
                <i class="bi bi-calculator"></i> <?= SYSTEM_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">대시보드</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">거래내역</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal_entry.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">전표입력</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            재무제표
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="balance_sheet.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">재무상태표</a></li>
                            <li><a class="dropdown-item" href="income_statement.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">손익계산서</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            관리
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="account_mapping.php">계정 매핑</a></li>
                            <li><a class="dropdown-item" href="debt_management.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">채권/채무</a></li>
                            <li><a class="dropdown-item" href="asset_management.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">고정자산</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="ms-auto text-light">
                    <span class="me-3">
                        <i class="bi bi-person"></i> <?= h($user['full_name'] ?? '사용자') ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2 class="mb-4">거래처별 계정 매핑 설정</h2>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            거래처별로 자동으로 사용할 계정을 설정합니다. 전표 입력시 거래처를 선택하면 설정된 계정이 자동으로 선택됩니다.
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">거래처 목록</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="200">거래처명</th>
                                <th>유형</th>
                                <th>채권 계정</th>
                                <th>채무 계정</th>
                                <th width="150">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($counterparties)): ?>
                                <?php foreach ($counterparties as $cp): ?>
                                <?php 
                                    $mapping = $mappingsByCounterparty[$cp['counterparty_id']] ?? null;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= h($cp['name']) ?></strong>
                                        <?php if ($cp['linked_company_id']): ?>
                                        <span class="badge bg-info">연결회사</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mapping): ?>
                                            <?= $mapping['direction'] == 'receivable' ? 
                                                '<span class="text-success">채권</span>' : 
                                                '<span class="text-danger">채무</span>' ?>
                                        <?php else: ?>
                                            <span class="text-muted">미설정</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mapping && $mapping['direction'] == 'receivable'): ?>
                                            <?= h($mapping['my_account_name'] ?? '미설정') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mapping && $mapping['direction'] == 'payable'): ?>
                                            <?= h($mapping['my_account_name'] ?? '미설정') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="openMappingModal('<?= $cp['counterparty_id'] ?>', '<?= h($cp['name']) ?>', <?= $mapping ? json_encode($mapping) : 'null' ?>)">
                                            <i class="bi bi-gear"></i> 설정
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        등록된 거래처가 없습니다.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 계정 매핑 모달 -->
    <div class="modal fade" id="mappingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">계정 매핑 설정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="mappingForm">
                    <div class="modal-body">
                        <input type="hidden" id="mapping_id" name="mapping_id">
                        <input type="hidden" id="counterparty_id" name="counterparty_id">
                        <input type="hidden" name="company_id" value="<?= $company_id ?>">
                        <input type="hidden" name="created_by" value="<?= $user_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">거래처명</label>
                            <input type="text" class="form-control" id="counterparty_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">거래 유형 <span class="text-danger">*</span></label>
                            <select class="form-select" name="direction" id="direction" required onchange="updateAccountOptions()">
                                <option value="">선택하세요</option>
                                <option value="receivable">채권 (받을 돈)</option>
                                <option value="payable">채무 (줄 돈)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">사용할 계정 <span class="text-danger">*</span></label>
                            <select class="form-select" name="my_account_id" id="my_account_id" required>
                                <option value="">먼저 거래 유형을 선택하세요</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                이 설정은 전표 입력시 자동으로 계정을 선택하는데 사용됩니다.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">저장</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const receivableAccounts = <?= json_encode($receivableAccounts) ?>;
        const payableAccounts = <?= json_encode($payableAccounts) ?>;
        
        function openMappingModal(counterpartyId, counterpartyName, mapping) {
            document.getElementById('counterparty_id').value = counterpartyId;
            document.getElementById('counterparty_name').value = counterpartyName;
            
            if (mapping) {
                document.getElementById('mapping_id').value = mapping.mapping_id;
                document.getElementById('direction').value = mapping.direction;
                updateAccountOptions();
                document.getElementById('my_account_id').value = mapping.my_account_id;
            } else {
                document.getElementById('mappingForm').reset();
                document.getElementById('counterparty_id').value = counterpartyId;
                document.getElementById('counterparty_name').value = counterpartyName;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('mappingModal'));
            modal.show();
        }
        
        function updateAccountOptions() {
            const direction = document.getElementById('direction').value;
            const accountSelect = document.getElementById('my_account_id');
            
            accountSelect.innerHTML = '<option value="">계정을 선택하세요</option>';
            
            if (direction === 'receivable') {
                receivableAccounts.forEach(account => {
                    const option = document.createElement('option');
                    option.value = account.account_id;
                    option.textContent = account.account_name;
                    accountSelect.appendChild(option);
                });
            } else if (direction === 'payable') {
                payableAccounts.forEach(account => {
                    const option = document.createElement('option');
                    option.value = account.account_id;
                    option.textContent = account.account_name;
                    accountSelect.appendChild(option);
                });
            }
        }
        
        // 폼 제출
        document.getElementById('mappingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/save_account_mapping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('계정 매핑이 저장되었습니다.');
                    location.reload();
                } else {
                    alert('오류: ' + (result.error || '저장에 실패했습니다.'));
                }
            } catch (error) {
                alert('오류: ' + error.message);
            }
        });
    </script>
</body>
</html>

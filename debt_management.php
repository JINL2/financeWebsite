<?php
/**
 * 재무관리 시스템 - 채권/채무 관리
 */
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// 인증 확인
$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];
$store_id = $_GET['store_id'] ?? null;

// 사용자 정보
$user = getCurrentUser($user_id);
$companies = getUserCompanies($user_id);
$stores = getUserStores($user_id, $company_id);

// 필터 파라미터
$direction = $_GET['direction'] ?? null; // receivable/payable
$status = $_GET['status'] ?? 'unpaid'; // unpaid/paid/overdue

// 데이터베이스 연결
$db = new SupabaseDB();

try {
    // 채권/채무 요약 정보 조회
    $summary = $db->callRPC('get_debt_summary', [
        'p_company_id' => $company_id,
        'p_store_id' => $store_id
    ]);
    
    // 채권/채무 상세 목록 조회
    $params = [
        'company_id' => 'eq.' . $company_id
    ];
    
    if ($store_id) {
        $params['store_id'] = 'eq.' . $store_id;
    }
    
    if ($direction) {
        $params['direction'] = 'eq.' . $direction;
    }
    
    if ($status == 'unpaid') {
        $params['remaining_amount'] = ['operator' => 'gt', 'value' => 0];
    } elseif ($status == 'paid') {
        $params['remaining_amount'] = 'eq.0';
    } elseif ($status == 'overdue') {
        $params['is_overdue'] = 'eq.true';
    }
    
    $debts = $db->query('debts_receivable', 'GET', null, array_merge($params, [
        'order' => 'due_date.asc,created_at.desc'
    ]));
    
    // 총계 계산
    $totals = [
        'receivable' => 0,
        'payable' => 0,
        'overdue_receivable' => 0,
        'overdue_payable' => 0,
        'due_today' => 0
    ];
    
    foreach ($debts as $debt) {
        if ($debt['direction'] == 'receivable') {
            $totals['receivable'] += $debt['remaining_amount'];
            if ($debt['is_overdue']) {
                $totals['overdue_receivable'] += $debt['remaining_amount'];
            }
        } else {
            $totals['payable'] += $debt['remaining_amount'];
            if ($debt['is_overdue']) {
                $totals['overdue_payable'] += $debt['remaining_amount'];
            }
        }
        
        if ($debt['due_date'] == date('Y-m-d')) {
            $totals['due_today'] += $debt['remaining_amount'];
        }
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
    <title>채권/채무 관리 - <?= SYSTEM_NAME ?></title>
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
                            <li><a class="dropdown-item" href="account_mapping.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">계정 매핑</a></li>
                            <li><a class="dropdown-item active" href="debt_management.php">채권/채무</a></li>
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
        <h2 class="mb-4">채권/채무 관리</h2>

        <!-- 요약 카드 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-arrow-down-circle"></i> 총 채권 (받을 돈)
                        </h6>
                        <h3 class="text-success mb-0">₩<?= formatMoney($totals['receivable']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-arrow-up-circle"></i> 총 채무 (줄 돈)
                        </h6>
                        <h3 class="text-danger mb-0">₩<?= formatMoney($totals['payable']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-exclamation-triangle"></i> 연체 금액
                        </h6>
                        <h3 class="text-warning mb-0">₩<?= formatMoney($totals['overdue_receivable'] + $totals['overdue_payable']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-calendar-event"></i> 오늘 만기
                        </h6>
                        <h3 class="text-info mb-0">₩<?= formatMoney($totals['due_today']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- 필터 -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <input type="hidden" name="company_id" value="<?= $company_id ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">가게</label>
                            <select name="store_id" class="form-select">
                                <option value="">전체</option>
                                <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['store_id'] ?>" <?= $store_id == $store['store_id'] ? 'selected' : '' ?>>
                                    <?= h($store['store_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">유형</label>
                            <select name="direction" class="form-select">
                                <option value="">전체</option>
                                <option value="receivable" <?= $direction == 'receivable' ? 'selected' : '' ?>>채권</option>
                                <option value="payable" <?= $direction == 'payable' ? 'selected' : '' ?>>채무</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                <option value="unpaid" <?= $status == 'unpaid' ? 'selected' : '' ?>>미지급</option>
                                <option value="overdue" <?= $status == 'overdue' ? 'selected' : '' ?>>연체</option>
                                <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>완납</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> 조회
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- 채권/채무 목록 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">채권/채무 내역</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>거래처</th>
                                <th>유형</th>
                                <th>발생일</th>
                                <th>만기일</th>
                                <th class="text-end">원금</th>
                                <th class="text-end">잔액</th>
                                <th>이자율</th>
                                <th>상태</th>
                                <th width="150">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($debts)): ?>
                                <?php foreach ($debts as $debt): ?>
                                <tr class="<?= $debt['is_overdue'] ? 'table-warning' : '' ?>">
                                    <td>
                                        <strong><?= h($debt['counterparty_name'] ?? '거래처 미상') ?></strong>
                                    </td>
                                    <td>
                                        <?= $debt['direction'] == 'receivable' ? 
                                            '<span class="badge bg-success">채권</span>' : 
                                            '<span class="badge bg-danger">채무</span>' ?>
                                    </td>
                                    <td><?= formatDate($debt['issue_date']) ?></td>
                                    <td>
                                        <?= formatDate($debt['due_date']) ?>
                                        <?php if ($debt['is_overdue']): ?>
                                        <span class="badge bg-warning">연체</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">₩<?= formatMoney($debt['original_amount']) ?></td>
                                    <td class="text-end">
                                        <strong>₩<?= formatMoney($debt['remaining_amount']) ?></strong>
                                    </td>
                                    <td>
                                        <?= $debt['interest_rate'] > 0 ? number_format($debt['interest_rate'], 1) . '%' : '-' ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $paidRatio = ($debt['original_amount'] - $debt['remaining_amount']) / $debt['original_amount'] * 100;
                                        ?>
                                        <div class="progress" style="width: 100px;">
                                            <div class="progress-bar" style="width: <?= $paidRatio ?>%">
                                                <?= number_format($paidRatio, 0) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewDebtDetail('<?= $debt['debt_id'] ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($debt['remaining_amount'] > 0): ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="recordPayment('<?= $debt['debt_id'] ?>', '<?= $debt['direction'] ?>', <?= $debt['remaining_amount'] ?>)">
                                            <i class="bi bi-cash"></i> <?= $debt['direction'] == 'receivable' ? '수금' : '지급' ?>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        조회된 채권/채무가 없습니다.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 상세 보기 모달 -->
    <div class="modal fade" id="debtDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">채권/채무 상세</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="debtDetailBody">
                    <!-- AJAX로 내용 로드 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 수금/지급 모달 -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalTitle">수금/지급 처리</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="payment_debt_id" name="debt_id">
                        <input type="hidden" id="payment_direction" name="direction">
                        
                        <div class="mb-3">
                            <label class="form-label">잔액</label>
                            <input type="text" class="form-control" id="remaining_amount_display" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">처리 금액 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₩</span>
                                <input type="number" class="form-control" name="amount" id="payment_amount" 
                                       required min="0" step="1">
                            </div>
                            <div class="form-text">
                                전액 처리하려면 잔액과 동일한 금액을 입력하세요.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">처리일 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">메모</label>
                            <input type="text" class="form-control" name="memo" 
                                   placeholder="처리 내용을 입력하세요">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">처리</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 채권/채무 상세 보기
        function viewDebtDetail(debtId) {
            const modal = new bootstrap.Modal(document.getElementById('debtDetailModal'));
            document.getElementById('debtDetailBody').innerHTML = '로딩 중...';
            modal.show();
            
            // TODO: AJAX로 상세 정보 로드
            setTimeout(() => {
                document.getElementById('debtDetailBody').innerHTML = `
                    <p>채권/채무 ID: ${debtId}</p>
                    <p>상세 정보 및 거래 내역을 불러오는 기능은 API 구현 후 추가됩니다.</p>
                `;
            }, 500);
        }
        
        // 수금/지급 처리
        function recordPayment(debtId, direction, remainingAmount) {
            document.getElementById('payment_debt_id').value = debtId;
            document.getElementById('payment_direction').value = direction;
            document.getElementById('remaining_amount_display').value = '₩' + remainingAmount.toLocaleString();
            document.getElementById('payment_amount').value = remainingAmount;
            document.getElementById('payment_amount').max = remainingAmount;
            
            document.getElementById('paymentModalTitle').textContent = 
                direction === 'receivable' ? '수금 처리' : '지급 처리';
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
        
        // 수금/지급 폼 제출
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // TODO: API 호출하여 수금/지급 처리
            alert('수금/지급 처리 기능은 API 구현 후 추가됩니다.');
            
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        });
    </script>
</body>
</html>

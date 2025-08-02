<?php
/**
 * 재무관리 시스템 - 고정자산 관리
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
$status = $_GET['status'] ?? 'active'; // active/disposed/all

// 데이터베이스 연결
$db = new SupabaseDB();

try {
    // 고정자산 목록 조회
    $params = [
        'company_id' => 'eq.' . $company_id
    ];
    
    if ($store_id) {
        $params['store_id'] = 'eq.' . $store_id;
    }
    
    if ($status == 'active') {
        $params['disposal_date'] = 'is.null';
    } elseif ($status == 'disposed') {
        $params['disposal_date'] = ['operator' => 'not.is', 'value' => 'null'];
    }
    
    $assets = $db->query('fixed_assets', 'GET', null, array_merge($params, [
        'order' => 'acquisition_date.desc,asset_name'
    ]));
    
    // 총계 계산
    $totals = [
        'acquisition_cost' => 0,
        'accumulated_depreciation' => 0,
        'book_value' => 0,
        'count' => 0
    ];
    
    // 각 자산의 감가상각 계산
    foreach ($assets as &$asset) {
        // 감가상각 기간 계산
        $acquisitionDate = new DateTime($asset['acquisition_date']);
        $currentDate = new DateTime();
        if ($asset['disposal_date']) {
            $currentDate = new DateTime($asset['disposal_date']);
        }
        
        $interval = $acquisitionDate->diff($currentDate);
        $monthsUsed = ($interval->y * 12) + $interval->m;
        $totalMonths = $asset['useful_life_years'] * 12;
        
        // 정액법 감가상각
        $depreciableAmount = $asset['acquisition_cost'] - $asset['salvage_value'];
        $monthlyDepreciation = $depreciableAmount / $totalMonths;
        $accumulatedDepreciation = min($monthsUsed * $monthlyDepreciation, $depreciableAmount);
        
        $asset['accumulated_depreciation'] = $accumulatedDepreciation;
        $asset['book_value'] = $asset['acquisition_cost'] - $accumulatedDepreciation;
        $asset['depreciation_rate'] = min(($accumulatedDepreciation / $depreciableAmount) * 100, 100);
        
        // 총계 업데이트
        if (!$asset['disposal_date']) {
            $totals['acquisition_cost'] += $asset['acquisition_cost'];
            $totals['accumulated_depreciation'] += $accumulatedDepreciation;
            $totals['book_value'] += $asset['book_value'];
            $totals['count']++;
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
    <title>고정자산 관리 - <?= SYSTEM_NAME ?></title>
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
                            <li><a class="dropdown-item" href="debt_management.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">채권/채무</a></li>
                            <li><a class="dropdown-item active" href="asset_management.php">고정자산</a></li>
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
        <h2 class="mb-4">고정자산 관리</h2>

        <!-- 요약 카드 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-box"></i> 보유 자산
                        </h6>
                        <h3 class="mb-0"><?= number_format($totals['count']) ?>개</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-cash-stack"></i> 취득원가 합계
                        </h6>
                        <h3 class="mb-0">₩<?= formatMoney($totals['acquisition_cost']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-graph-down"></i> 감가상각누계
                        </h6>
                        <h3 class="text-danger mb-0">₩<?= formatMoney($totals['accumulated_depreciation']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            <i class="bi bi-calculator"></i> 장부가액
                        </h6>
                        <h3 class="text-primary mb-0">₩<?= formatMoney($totals['book_value']) ?></h3>
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
                        <div class="col-md-4">
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
                        
                        <div class="col-md-4">
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>사용중</option>
                                <option value="disposed" <?= $status == 'disposed' ? 'selected' : '' ?>>처분</option>
                                <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>전체</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> 조회
                                </button>
                                <a href="journal_entry.php?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&type=expense&category=asset_purchase" 
                                   class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> 자산 취득
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- 고정자산 목록 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">고정자산 목록</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>자산명</th>
                                <th>취득일</th>
                                <th>내용연수</th>
                                <th class="text-end">취득원가</th>
                                <th class="text-end">감가상각누계</th>
                                <th class="text-end">장부가액</th>
                                <th>감가상각률</th>
                                <th>상태</th>
                                <th width="100">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assets)): ?>
                                <?php foreach ($assets as $asset): ?>
                                <tr class="<?= $asset['disposal_date'] ? 'text-muted' : '' ?>">
                                    <td>
                                        <strong><?= h($asset['asset_name']) ?></strong>
                                    </td>
                                    <td><?= formatDate($asset['acquisition_date']) ?></td>
                                    <td><?= $asset['useful_life_years'] ?>년</td>
                                    <td class="text-end">₩<?= formatMoney($asset['acquisition_cost']) ?></td>
                                    <td class="text-end text-danger">₩<?= formatMoney($asset['accumulated_depreciation']) ?></td>
                                    <td class="text-end">
                                        <strong>₩<?= formatMoney($asset['book_value']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 100px; height: 20px;">
                                            <div class="progress-bar <?= $asset['depreciation_rate'] >= 80 ? 'bg-warning' : '' ?>" 
                                                 style="width: <?= $asset['depreciation_rate'] ?>%">
                                                <?= number_format($asset['depreciation_rate'], 0) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($asset['disposal_date']): ?>
                                            <span class="badge bg-secondary">처분</span>
                                            <br><small><?= formatDate($asset['disposal_date']) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-success">사용중</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewAssetDetail('<?= $asset['asset_id'] ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (!$asset['disposal_date']): ?>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="disposeAsset('<?= $asset['asset_id'] ?>', '<?= h($asset['asset_name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        등록된 고정자산이 없습니다.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 자산 상세 모달 -->
    <div class="modal fade" id="assetDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">고정자산 상세</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="assetDetailBody">
                    <!-- AJAX로 내용 로드 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 자산 처분 모달 -->
    <div class="modal fade" id="disposeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">자산 처분</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="disposeForm">
                    <div class="modal-body">
                        <input type="hidden" id="dispose_asset_id" name="asset_id">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong id="dispose_asset_name"></strong> 자산을 처분하시겠습니까?
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">처분일 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="disposal_date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">처분금액</label>
                            <div class="input-group">
                                <span class="input-group-text">₩</span>
                                <input type="number" class="form-control" name="disposal_amount" 
                                       min="0" step="1" value="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">처분사유</label>
                            <select class="form-select" name="disposal_reason">
                                <option value="sale">매각</option>
                                <option value="discard">폐기</option>
                                <option value="donation">기증</option>
                                <option value="other">기타</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">메모</label>
                            <textarea class="form-control" name="memo" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-danger">처분</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 자산 상세 보기
        function viewAssetDetail(assetId) {
            const modal = new bootstrap.Modal(document.getElementById('assetDetailModal'));
            document.getElementById('assetDetailBody').innerHTML = '로딩 중...';
            modal.show();
            
            // TODO: AJAX로 상세 정보 로드
            setTimeout(() => {
                document.getElementById('assetDetailBody').innerHTML = `
                    <p>고정자산 ID: ${assetId}</p>
                    <p>감가상각 내역 및 상세 정보를 불러오는 기능은 API 구현 후 추가됩니다.</p>
                `;
            }, 500);
        }
        
        // 자산 처분
        function disposeAsset(assetId, assetName) {
            document.getElementById('dispose_asset_id').value = assetId;
            document.getElementById('dispose_asset_name').textContent = assetName;
            
            const modal = new bootstrap.Modal(document.getElementById('disposeModal'));
            modal.show();
        }
        
        // 처분 폼 제출
        document.getElementById('disposeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // TODO: API 호출하여 자산 처분 처리
            alert('자산 처분 기능은 API 구현 후 추가됩니다.');
            
            bootstrap.Modal.getInstance(document.getElementById('disposeModal')).hide();
        });
    </script>
</body>
</html>

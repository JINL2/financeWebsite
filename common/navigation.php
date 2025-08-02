<?php
/**
 * 공통 네비게이션 헤더
 * 
 * 필요한 변수:
 * - $user_id
 * - $company_id
 * - $user
 * - $companies
 * - $active_page (현재 페이지 표시용)
 */
?>
<!-- 네비게이션 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard/">
            <i class="bi bi-calculator"></i> <?= SYSTEM_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?= $active_page == 'dashboard' ? 'active' : '' ?>" href="../dashboard/">대시보드</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page == 'transactions' ? 'active' : '' ?>" href="../transactions/">거래내역</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page == 'journal-entry' ? 'active' : '' ?>" href="../journal-entry/">전표입력</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($active_page, ['balance-sheet', 'income-statement']) ? 'active' : '' ?>" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        재무제표
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?= $active_page == 'balance-sheet' ? 'active' : '' ?>" href="../balance-sheet/">재무상태표</a></li>
                        <li><a class="dropdown-item <?= $active_page == 'income-statement' ? 'active' : '' ?>" href="../income-statement/">손익계산서</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        관리
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../account-mapping/">계정 매핑</a></li>
                        <li><a class="dropdown-item" href="../debt-management/">채권/채무</a></li>
                        <li><a class="dropdown-item" href="../asset-management/">고정자산</a></li>
                    </ul>
                </li>
            </ul>
            <div class="ms-auto text-light">
                <span class="me-3">
                    <i class="bi bi-person"></i> <?= h($user['full_name'] ?? '사용자') ?>
                </span>
                <span class="me-3">
                    <i class="bi bi-building"></i> 
                    <select class="form-select form-select-sm d-inline-block w-auto" onchange="changeCompany(this.value)">
                        <?php foreach ($companies as $comp): ?>
                        <option value="<?= $comp['company_id'] ?>" <?= $comp['company_id'] == $company_id ? 'selected' : '' ?>>
                            <?= h($comp['company_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </div>
        </div>
    </div>
</nav>

<script>
function changeCompany(companyId) {
    const currentPath = window.location.pathname;
    const pathParts = currentPath.split('/');
    const moduleName = pathParts[pathParts.length - 2]; // 현재 모듈 이름
    window.location.href = `../${moduleName}/?user_id=<?= $user_id ?>&company_id=${companyId}`;
}
</script>

<?php
require_once '../common/config.php';
require_once '../common/auth.php';

// URL 파라미터에서 사용자 정보 가져오기
$user_id = $_GET['user_id'] ?? '';
$company_id = $_GET['company_id'] ?? '';

if (empty($user_id) || empty($company_id)) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Options - Financial Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 커스텀 스타일 */
        .filter-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .filter-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
            margin: 20px;
        }
        
        .calendar-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 20px;
            margin: 20px;
            position: relative;
        }
        
        .year-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .year-display {
            font-size: 2rem;
            color: white;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .nav-button {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .nav-button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .months-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .month-button {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .month-button:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .month-button.selected {
            background: white;
            color: #667eea;
            border-color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .close-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .close-button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="filter-container">
        <div class="filter-card">
            <!-- 헤더 -->
            <div class="p-8 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-filter mr-3 text-blue-600"></i>Filter Options
                </h1>
                <p class="text-gray-600">Select your filtering preferences for reports</p>
            </div>
            
            <!-- 필터 옵션들 -->
            <div class="p-8">
                <!-- Company 선택 -->
                <div class="mb-8">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">Company</label>
                    <select id="companySelect" class="w-full p-4 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none text-lg">
                        <option value="">Select Company</option>
                        <option value="7a2545e0-e112-4b0c-9c59-221a530c4602">test1</option>
                        <option value="ebd66ba7-fde7-4332-b6b5-0d8a7f615497">Test Company 2</option>
                        <option value="company3">Test Company 3</option>
                        <option value="company4">Cameraon&Headsup</option>
                    </select>
                </div>
                
                <!-- Period 선택 -->
                <div class="mb-8">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">Period</label>
                    <button id="periodSelect" class="w-full p-4 border-2 border-gray-300 rounded-xl hover:border-blue-500 focus:outline-none text-lg text-left bg-white flex items-center justify-between">
                        <span id="periodDisplay">2025</span>
                        <i class="fas fa-calendar-alt text-blue-600"></i>
                    </button>
                </div>
                
                <!-- 옵션 체크박스 -->
                <div class="mb-8">
                    <label class="flex items-center text-lg">
                        <input type="checkbox" id="includeZeroBalance" class="mr-3 w-5 h-5 text-blue-600">
                        <span class="text-gray-700">Include zero balance accounts</span>
                    </label>
                </div>
                
                <!-- 검색 버튼 -->
                <div class="text-center">
                    <button id="searchButton" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-12 rounded-xl text-lg transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-search mr-3"></i>Search
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Period 선택 모달 -->
    <div id="periodModal" class="modal-overlay">
        <div class="calendar-container">
            <button id="closeModal" class="close-button">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="year-selector">
                <div class="flex items-center justify-center space-x-6">
                    <button id="prevYear" class="nav-button">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div id="yearDisplay" class="year-display">2025</div>
                    <button id="nextYear" class="nav-button">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="months-grid">
                <button class="month-button" data-month="1">Jan</button>
                <button class="month-button" data-month="2">Feb</button>
                <button class="month-button" data-month="3">Mar</button>
                <button class="month-button" data-month="4">Apr</button>
                <button class="month-button" data-month="5">May</button>
                <button class="month-button" data-month="6">Jun</button>
                <button class="month-button selected" data-month="7">Jul</button>
                <button class="month-button" data-month="8">Aug</button>
                <button class="month-button" data-month="9">Sep</button>
                <button class="month-button" data-month="10">Oct</button>
                <button class="month-button" data-month="11">Nov</button>
                <button class="month-button" data-month="12">Dec</button>
            </div>
            
            <div class="text-center mt-8">
                <p class="text-white text-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    Click on any month to select that period
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentYear = 2025;
        let selectedMonth = 7;
        
        // DOM 요소들
        const periodSelect = document.getElementById('periodSelect');
        const periodModal = document.getElementById('periodModal');
        const closeModal = document.getElementById('closeModal');
        const yearDisplay = document.getElementById('yearDisplay');
        const prevYear = document.getElementById('prevYear');
        const nextYear = document.getElementById('nextYear');
        const monthButtons = document.querySelectorAll('.month-button');
        const periodDisplay = document.getElementById('periodDisplay');
        const companySelect = document.getElementById('companySelect');
        const searchButton = document.getElementById('searchButton');
        
        // 초기값 설정
        companySelect.value = '<?php echo $company_id; ?>';
        
        // Period 버튼 클릭 시 모달 열기
        periodSelect.addEventListener('click', function() {
            periodModal.classList.add('active');
        });
        
        // 모달 닫기
        closeModal.addEventListener('click', function() {
            periodModal.classList.remove('active');
        });
        
        // 모달 외부 클릭 시 닫기
        periodModal.addEventListener('click', function(e) {
            if (e.target === periodModal) {
                periodModal.classList.remove('active');
            }
        });
        
        // 년도 변경
        prevYear.addEventListener('click', function() {
            if (currentYear > 2020) {
                currentYear--;
                updateYearDisplay();
            }
        });
        
        nextYear.addEventListener('click', function() {
            if (currentYear < 2030) {
                currentYear++;
                updateYearDisplay();
            }
        });
        
        // 년도 표시 업데이트
        function updateYearDisplay() {
            yearDisplay.textContent = currentYear;
        }
        
        // 월 선택
        monthButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 모든 버튼에서 selected 클래스 제거
                monthButtons.forEach(btn => btn.classList.remove('selected'));
                
                // 클릭된 버튼에 selected 클래스 추가
                this.classList.add('selected');
                
                selectedMonth = parseInt(this.dataset.month);
                
                // Period 표시 업데이트
                const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                periodDisplay.textContent = `${monthNames[selectedMonth]} ${currentYear}`;
                
                // 모달 닫기
                setTimeout(() => {
                    periodModal.classList.remove('active');
                }, 300);
            });
        });
        
        // 검색 버튼 클릭
        searchButton.addEventListener('click', function() {
            const selectedCompany = companySelect.value;
            
            if (!selectedCompany) {
                alert('Please select a company first.');
                return;
            }
            
            // Income Statement 페이지로 이동
            const url = `../income-statement/?user_id=<?php echo $user_id; ?>&company_id=${selectedCompany}&year=${currentYear}&month=${selectedMonth}`;
            window.location.href = url;
        });
        
        // 키보드 이벤트 (ESC로 모달 닫기)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                periodModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>

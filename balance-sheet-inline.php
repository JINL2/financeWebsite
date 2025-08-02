<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet - Financial Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 인라인 달력 스타일 */
        .calendar-container {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            min-height: 400px;
        }
        
        .year-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .year-display {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        
        .months-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        .month-button {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
        }
        
        .month-button:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .month-button.selected {
            background: #3b82f6;
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        .filter-panel {
            background: #f8fafc;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        .period-input {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .search-button {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Balance Sheet</h1>
                    <p class="text-gray-600 mt-1">Real-time view of company's assets, liabilities, and equity</p>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-user-circle mr-2"></i>
                    User ID: test1
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="grid grid-cols-12 gap-8">
            <!-- Left Filter Panel -->
            <div class="col-span-4">
                <div class="filter-panel">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-filter mr-3 text-blue-600"></i>
                            Filter Options
                        </h2>
                        
                        <!-- Company Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Company</label>
                            <select id="companySelect" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                                <option value="">Select Company</option>
                                <option value="7a2545e0-e112-4b0c-9c59-221a530c4602" selected>test1</option>
                                <option value="ebd66ba7-fde7-4332-b6b5-0d8a7f615497">Test Company 2</option>
                                <option value="company3">Test Company 3</option>
                                <option value="company4">Cameraon&Headsup</option>
                            </select>
                        </div>
                        
                        <!-- Search Button -->
                        <button id="searchButton" class="search-button w-full mb-6">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        
                        <!-- Zero Balance Checkbox -->
                        <div class="flex items-center">
                            <input type="checkbox" id="includeZeroBalance" class="mr-3 w-4 h-4 text-blue-600">
                            <label for="includeZeroBalance" class="text-gray-700">Include zero balance accounts</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Content Area -->
            <div class="col-span-8">
                <!-- Period Selection -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">Period</label>
                    <input 
                        type="text" 
                        id="periodInput" 
                        class="period-input w-full"
                        value="2025"
                        readonly
                        placeholder="Select Period"
                    >
                </div>
                
                <!-- Inline Calendar -->
                <div class="calendar-container">
                    <div class="year-selector">
                        <h3 id="yearDisplay" class="year-display">2025</h3>
                    </div>
                    
                    <div class="months-grid" id="monthsGrid">
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
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentYear = 2025;
        let selectedMonth = 7;
        
        // DOM 요소들
        const yearDisplay = document.getElementById('yearDisplay');
        const monthsGrid = document.getElementById('monthsGrid');
        const periodInput = document.getElementById('periodInput');
        const companySelect = document.getElementById('companySelect');
        const searchButton = document.getElementById('searchButton');
        
        // 월 이름 배열
        const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // 초기 설정
        updatePeriodDisplay();
        
        // 월 버튼 클릭 이벤트
        monthsGrid.addEventListener('click', function(e) {
            if (e.target.classList.contains('month-button')) {
                // 모든 버튼에서 selected 클래스 제거
                document.querySelectorAll('.month-button').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // 클릭된 버튼에 selected 클래스 추가
                e.target.classList.add('selected');
                
                selectedMonth = parseInt(e.target.dataset.month);
                updatePeriodDisplay();
            }
        });
        
        // Period 표시 업데이트
        function updatePeriodDisplay() {
            periodInput.value = `${monthNames[selectedMonth]} ${currentYear}`;
            yearDisplay.textContent = currentYear;
        }
        
        // 검색 버튼 클릭
        searchButton.addEventListener('click', function() {
            const selectedCompany = companySelect.value;
            
            if (!selectedCompany) {
                alert('Please select a company first.');
                return;
            }
            
            alert(`Search with:\nCompany: ${selectedCompany}\nPeriod: ${monthNames[selectedMonth]} ${currentYear}`);
        });
        
        // 년도 변경 (키보드 이벤트)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentYear > 2020) {
                currentYear--;
                updatePeriodDisplay();
            } else if (e.key === 'ArrowRight' && currentYear < 2030) {
                currentYear++;
                updatePeriodDisplay();
            }
        });
    </script>
</body>
</html>

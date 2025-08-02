<?php
/**
 * Journal Entry 저장 완료 후 Counterparty Cash Location 적용
 * insert_journal_with_everything 완료 후 호출됨
 * 
 * 작성일: 2025-07-22
 * 용도: Internal Transaction에서 상대방 매장의 Cash Location 설정
 */

require_once '../../../config/supabase_config.php';

/**
 * Journal Entry 저장 완료 후 Counterparty Cash Location 적용
 * @param string $journal_id Journal Entry ID
 * @param string $counterparty_cash_location_id 상대방 Cash Location ID
 * @return array 처리 결과
 */
function applyCounterpartyCashLocation($journal_id, $counterparty_cash_location_id) {
    try {
        // 입력 값 검증
        if (empty($journal_id) || empty($counterparty_cash_location_id)) {
            return [
                'success' => false,
                'error' => 'Journal ID와 Counterparty Cash Location ID가 필요합니다.'
            ];
        }

        // Supabase 새 RPC 함수 호출
        $supabase_data = [
            'p_journal_id' => $journal_id,
            'p_counterparty_cash_location_id' => $counterparty_cash_location_id
        ];

        $result = callSupabaseFunction('update_mirror_journal_cash_location', $supabase_data);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Counterparty Cash Location이 성공적으로 적용되었습니다.',
                'data' => $result['data']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Counterparty Cash Location 적용 중 오류: ' . $result['error']
            ];
        }

    } catch (Exception $e) {
        error_log("applyCounterpartyCashLocation 오류: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Counterparty Cash Location 적용 중 예외 발생: ' . $e->getMessage()
        ];
    }
}

/**
 * Supabase RPC 함수 호출
 * @param string $function_name 함수명
 * @param array $data 파라미터
 * @return array 결과
 */
function callSupabaseFunction($function_name, $data) {
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/rest/v1/rpc/' . $function_name;
    
    $options = [
        'http' => [
            'header' => [
                "Content-type: application/json",
                "Authorization: Bearer " . $supabase_key,
                "apikey: " . $supabase_key
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return [
            'success' => false,
            'error' => 'Supabase 함수 호출 실패'
        ];
    }

    $result = json_decode($response, true);
    
    // Supabase 오류 응답 처리
    if (isset($result['error'])) {
        return [
            'success' => false,
            'error' => $result['error']['message'] ?? 'Unknown error'
        ];
    }

    return [
        'success' => true,
        'data' => $result
    ];
}

// API 직접 호출 시 처리 (Ajax 요청용)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'error' => '잘못된 JSON 데이터'
        ]);
        exit;
    }

    $journal_id = $input['journal_id'] ?? '';
    $counterparty_cash_location_id = $input['counterparty_cash_location_id'] ?? '';

    $result = applyCounterpartyCashLocation($journal_id, $counterparty_cash_location_id);
    echo json_encode($result);
    exit;
}
?>
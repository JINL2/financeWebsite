<?php
/**
 * Financial Management System - Templates API
 * Phase 4.3.2: Templates 기능 구현
 */

require_once '../common/auth.php';
require_once '../common/functions.php';

// JSON response header
header('Content-Type: application/json');

try {
    // Authentication check
    $auth = requireAuth();
    $user_id = $auth['user_id'];
    $company_id = $auth['company_id'];
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            echo json_encode(getTemplates($user_id, $company_id));
            break;
            
        case 'save':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(saveTemplate($user_id, $company_id, $input));
            break;
            
        case 'get':
            $template_id = $_GET['template_id'] ?? '';
            echo json_encode(getTemplate($template_id, $user_id, $company_id));
            break;
            
        case 'delete':
            $template_id = $_GET['template_id'] ?? '';
            echo json_encode(deleteTemplate($template_id, $user_id, $company_id));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log('Templates API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}

/**
 * 템플릿 목록 조회
 */
function getTemplates($user_id, $company_id) {
    global $supabase;
    
    try {
        $response = $supabase->query('journal_templates', [
            'select' => 'template_id,template_name,created_at',
            'user_id' => 'eq.' . $user_id,
            'company_id' => 'eq.' . $company_id,
            'order' => 'created_at.desc'
        ]);
        
        return [
            'success' => true, 
            'templates' => $response ?? []
        ];
        
    } catch (Exception $e) {
        error_log('Error getting templates: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to load templates'];
    }
}

/**
 * 템플릿 저장
 */
function saveTemplate($user_id, $company_id, $input) {
    global $supabase;
    
    try {
        // 입력 검증
        if (!isset($input['template_name']) || !isset($input['template_data'])) {
            throw new Exception('Missing required fields');
        }
        
        $template_name = trim($input['template_name']);
        if (empty($template_name)) {
            throw new Exception('Template name is required');
        }
        
        // 템플릿 데이터 검증
        $template_data = $input['template_data'];
        if (empty($template_data['journal_lines']) || count($template_data['journal_lines']) < 2) {
            throw new Exception('Template must have at least 2 journal lines');
        }
        
        // 중복 이름 확인
        $existing = $supabase->query('journal_templates', [
            'select' => 'template_id',
            'user_id' => 'eq.' . $user_id,
            'company_id' => 'eq.' . $company_id,
            'template_name' => 'eq.' . $template_name
        ]);
        
        if (!empty($existing)) {
            throw new Exception('Template name already exists');
        }
        
        // 민감한 데이터 제거 (실제 금액, 날짜 등)
        $clean_data = cleanTemplateData($template_data);
        
        $template = [
            'user_id' => $user_id,
            'company_id' => $company_id,
            'template_name' => $template_name,
            'template_data' => json_encode($clean_data),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $response = $supabase->query('journal_templates', [], 'POST', $template);
        
        return [
            'success' => true, 
            'message' => 'Template saved successfully',
            'template_id' => $response[0]['template_id'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log('Error saving template: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 템플릿 조회
 */
function getTemplate($template_id, $user_id, $company_id) {
    global $supabase;
    
    try {
        if (empty($template_id)) {
            throw new Exception('Template ID is required');
        }
        
        $response = $supabase->query('journal_templates', [
            'select' => 'template_id,template_name,template_data,created_at',
            'template_id' => 'eq.' . $template_id,
            'user_id' => 'eq.' . $user_id,
            'company_id' => 'eq.' . $company_id
        ]);
        
        if (empty($response)) {
            throw new Exception('Template not found');
        }
        
        $template = $response[0];
        // template_data는 이미 jsonb에서 배열로 변환되어 있음
        if (is_string($template['template_data'])) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return [
            'success' => true, 
            'template' => $template
        ];
        
    } catch (Exception $e) {
        error_log('Error getting template: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 템플릿 삭제
 */
function deleteTemplate($template_id, $user_id, $company_id) {
    global $supabase;
    
    try {
        if (empty($template_id)) {
            throw new Exception('Template ID is required');
        }
        
        // 소유권 확인
        $existing = $supabase->query('journal_templates', [
            'select' => 'template_id',
            'template_id' => 'eq.' . $template_id,
            'user_id' => 'eq.' . $user_id,
            'company_id' => 'eq.' . $company_id
        ]);
        
        if (empty($existing)) {
            throw new Exception('Template not found or access denied');
        }
        
        $response = $supabase->query('journal_templates', [
            'template_id' => 'eq.' . $template_id
        ], 'DELETE');
        
        return [
            'success' => true, 
            'message' => 'Template deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log('Error deleting template: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 템플릿 데이터 정리 (민감한 정보 제거)
 */
function cleanTemplateData($data) {
    $clean_data = $data;
    
    // 날짜 제거 (템플릿에서는 사용하지 않음)
    unset($clean_data['entry_date']);
    
    // Reference Number 제거됨 (이미 제거됨)
    
    // 실제 금액을 0으로 초기화 (구조만 저장)
    if (isset($clean_data['journal_lines'])) {
        foreach ($clean_data['journal_lines'] as $index => $line) {
            $clean_data['journal_lines'][$index]['debit_amount'] = '';
            $clean_data['journal_lines'][$index]['credit_amount'] = '';
        }
    }
    
    return $clean_data;
}
?>
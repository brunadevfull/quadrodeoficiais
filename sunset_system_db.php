<?php
/**
 * Sistema de Pôr do Sol CHM - Versão com Banco de Dados
 * Centro de Hidrografia da Marinha - Dados Oficiais 2025
 */

class SunsetSystemDB {
    private static $pdo;
    
    /**
     * Inicializar o sistema com conexão do banco
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
    }
    
    /**
     * Obter horário do pôr do sol para hoje
     */
    public static function getTodaysSunsetTime() {
        return self::getSunsetTimeForDate(date('Y-m-d'));
    }
    
    /**
     * Obter horário do pôr do sol para uma data específica
     */
    public static function getSunsetTimeForDate($date) {
        try {
            $stmt = self::$pdo->prepare('
                SELECT por_do_sol 
                FROM chm_horarios 
                WHERE data = ? 
                LIMIT 1
            ');
            $stmt->execute([$date]);
            $result = $stmt->fetchColumn();
            
            // PostgreSQL retorna o horário no formato HH:MM:SS, vamos converter para HH:MM
            if ($result) {
                return substr($result, 0, 5); // Remove os segundos
            }
            
            return '18:00'; // Fallback
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar horário CHM para {$date}: " . $e->getMessage());
            return '18:00'; // Fallback em caso de erro
        }
    }
    
    /**
     * Verificar se já passou do pôr do sol hoje
     */
    public static function hasSunsetPassedToday() {
        $now = new DateTime();
        $todaySunset = self::getTodaysSunsetTime();
        
        if (!$todaySunset || $todaySunset === '18:00') {
            return false; // Se não conseguiu obter horário, assume que não passou
        }
        
        list($hours, $minutes) = explode(':', $todaySunset);
        $sunsetTime = new DateTime();
        $sunsetTime->setTime($hours, $minutes);
        
        return $now > $sunsetTime;
    }
    
    /**
     * Obter informações completas do pôr do sol para uma data
     */
    public static function getSunsetInfo($date = null) {
        $date = $date ? $date : date('Y-m-d');
        
        try {
            $stmt = self::$pdo->prepare('
                SELECT data, por_do_sol, fonte, observacoes 
                FROM chm_horarios 
                WHERE data = ? 
                LIMIT 1
            ');
            $stmt->execute([$date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'date' => $result['data'],
                    'sunset_time' => substr($result['por_do_sol'], 0, 5), // Remove segundos
                    'source' => $result['fonte'],
                    'notes' => $result['observacoes'],
                    'has_passed' => ($date === date('Y-m-d')) ? self::hasSunsetPassedToday() : false,
                    'formatted_date' => date('d/m/Y', strtotime($result['data']))
                ];
            }
            
            return [
                'date' => $date,
                'sunset_time' => '18:00',
                'source' => 'Fallback',
                'notes' => 'Horário padrão',
                'has_passed' => false,
                'formatted_date' => date('d/m/Y', strtotime($date))
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar informações CHM para {$date}: " . $e->getMessage());
            return [
                'date' => $date,
                'sunset_time' => '18:00',
                'source' => 'Error',
                'notes' => 'Erro ao consultar banco',
                'has_passed' => false,
                'formatted_date' => date('d/m/Y', strtotime($date))
            ];
        }
    }
    
    /**
     * Obter próximos 7 dias de horários
     */
    public static function getWeekSunsetTimes() {
        try {
            $stmt = self::$pdo->prepare('
                SELECT data, por_do_sol, fonte 
                FROM chm_horarios 
                WHERE data >= CURRENT_DATE 
                ORDER BY data 
                LIMIT 7
            ');
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatar os horários (remover segundos)
            foreach ($results as &$result) {
                $result['por_do_sol'] = substr($result['por_do_sol'], 0, 5);
                $result['formatted_date'] = date('d/m/Y', strtotime($result['data']));
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar horários da semana: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gerar dados JSON para JavaScript (apenas próximos 30 dias para performance)
     */
    public static function getJavaScriptData() {
        try {
            $stmt = self::$pdo->prepare('
                SELECT data, por_do_sol 
                FROM chm_horarios 
                WHERE data >= CURRENT_DATE AND data <= CURRENT_DATE + INTERVAL \'30 days\'
                ORDER BY data
            ');
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $jsData = [];
            foreach ($results as $result) {
                $jsData[$result['data']] = substr($result['por_do_sol'], 0, 5);
            }
            
            return json_encode($jsData);
            
        } catch (PDOException $e) {
            error_log("Erro ao gerar dados JavaScript: " . $e->getMessage());
            return '{}';
        }
    }
    
    /**
     * Debug: verificar se o sistema está funcionando
     */
    public static function debug() {
        if (!self::$pdo) {
            return "ERRO: PDO não inicializado";
        }
        
        try {
            $stmt = self::$pdo->query('SELECT COUNT(*) FROM chm_horarios');
            $count = $stmt->fetchColumn();
            
            $today = date('Y-m-d');
            $todayTime = self::getTodaysSunsetTime();
            
            return [
                'status' => 'OK',
                'total_records' => $count,
                'today_date' => $today,
                'today_sunset' => $todayTime,
                'has_passed' => self::hasSunsetPassedToday()
            ];
            
        } catch (PDOException $e) {
            return "ERRO: " . $e->getMessage();
        }
    }
}

// Função global para compatibilidade
function getSunsetTime($date = null) {
    return SunsetSystemDB::getSunsetTimeForDate($date ?: date('Y-m-d'));
}

// Auto-inicialização se PDO estiver disponível
if (isset($pdo) && $pdo instanceof PDO) {
    SunsetSystemDB::init($pdo);
}
?>
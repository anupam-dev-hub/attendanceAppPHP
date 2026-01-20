<?php
class StudentStats {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }



    public function getAttendanceHistory($student_id, $limit = 30) {
        $history = [];
        $sql = "SELECT date, in_time, out_time 
                FROM attendance 
                WHERE student_id = ? 
                ORDER BY date DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check if both in_time and out_time are blank (NULL or empty)
            $in_time = $row['in_time'];
            $out_time = $row['out_time'];
            
            // Determine status: Absent if no in/out times, Present if at least one time exists
            if (empty($in_time) && empty($out_time)) {
                $status = 'Absent';
                $in_display = '-';
                $out_display = '-';
            } else {
                $status = 'Present';
                $in_display = $in_time ? date('h:i A', strtotime($in_time)) : '-';
                $out_display = $out_time ? date('h:i A', strtotime($out_time)) : '-';
            }
            
            $history[] = [
                'date' => date('d M Y', strtotime($row['date'])),
                'in_time' => $in_display,
                'out_time' => $out_display,
                'status' => $status
            ];
        }
        
        return $history;
    }

    public function getAttendanceStats($student_id) {
        // Get monthly attendance counts for the last 6 months
        $stats = [];
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as count 
                FROM attendance 
                WHERE student_id = ? 
                AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY month 
                ORDER BY month ASC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('M Y', strtotime($row['month'] . '-01'));
            $data[] = $row['count'];
        }
        
        return ['labels' => $labels, 'data' => $data];
    }

}
?>

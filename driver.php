<?php
/**
 * driver_api.php
 * Full backend for the Driver Dashboard HTML
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

/**
 * ----------------------
 * DATABASE CONNECTION
 * ----------------------
 */
class Database {
    private $host = "mssql.cs.ucy.ac.cy";
    private $db_name = "eioann09";
    private $username = "eioann09";
    private $password = "CQxPy3nG";
    public $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO("sqlsrv:Server=".$this->host.";Database=".$this->db_name.";TrustServerCertificate=yes",
                                   $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e){
            error_log("DB Connection failed: ".$e->getMessage()); //if connection failed
        }
        return $this->conn;
    }
}

/**
 * ----------------------
 * HELPER FUNCTIONS
 * ----------------------
 */
function runSelect($query, $params=[]) {
    $db = new Database();
    $conn = $db->getConnection();
    if(!$conn) return [];
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $res ?: [];
}

function runExecute($query, $params=[]) {
    $db = new Database();
    $conn = $db->getConnection();
    if(!$conn) return false;
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}


function requireDriverAuth() {
    if(!isset($_SESSION['driver_id']) || !isset($_SESSION['users_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'error'=>'Authentication required']);
        exit;
    }
}

//these 3 get the user_id driver_id and driver name from the session variables
function getCurrentDriverId(){ return $_SESSION['driver_id'] ?? null; }
function getCurrentUserId(){ return $_SESSION['users_id'] ?? null; }
function getDriverName(){ return $_SESSION['driver_name'] ?? 'Driver'; }

/**
 * ----------------------
 * API ROUTING
 * ----------------------
 */
requireDriverAuth();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action){
        case 'get_pending_rides': getPendingRides(); break;
        case 'get_stats': getDriverStats(); break;
        case 'get_feedback': getDriverFeedback(); break;
        case 'chat_get': getChatMessages(); break;
        case 'chat_send': sendChatMessage(); break;
        case 'set_availability': setAvailability(); break;
        case 'get_driver_info': getDriverInfoAPI(); break;
        default: throw new Exception("Invalid action");
    }
} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}


//this function calls the SP to get all the pending rides the driver has to accept or reject
function getPendingRides(){
    $driver_id=getCurrentDriverId(); //the function i have pano that returns the driver's id from this session

    //call the store procedure
    $query = "EXEC getDriverRequests ?";
    $results = runSelect($query, [$driver_id]); //put the drivery id in the parameter for the getDriverRequests that has the parameter

    $pending=[]; //makes an array that is empty and will store everything

    foreach($results as $row){ //a for statement that runs for how many rows the $results returns

        $extra = getExtraRideInfo($row['ride_id']); //call the function below that gets extra ride info)

        $pending[]=[
            'ride_id' => $row['ride_id'], //the ride id

            'pickup_lat' => $row['pickup_lat'], //where the pick up will be
            'dropoff_lat' => $row['dropoff_lat'], //where the drop off will be
            'vehicle_type_id' => $row['vehicle_type_id'],//what type of vehicle
            'request_time' => $row['request_time'],//the time that it will take for the ride
            'status' => $row['status'], // status it's pending

            //this is extra stuff that we added that is the getExtraRideInfo function below us!!
            'customer_name' => $extra['customer_name'],
            'customer_phone' => $extra['customer_phone'],
            'customer_rating' => $extra['customer_rating'],
            'fare' => $extra['fare'],
            'distance' => $extra['distance'],
            'eta' => $extra['eta']
        ]; //store the stuff in the array
    }
}

//extra information about the ride request that we took
function getExtraRideInfo($ride_id) {
    $sql = "SELECT 
                u.name, u.last_name, u.phone,
                ISNULL(AVG(f.rating),0) AS rating,
                r.estimated_fare, r.estimated_distance, r.estimated_duration
            FROM RIDE r
            INNER JOIN USERS u ON r.users_id = u.users_id
            LEFT JOIN GIVEN_FEEDBACK gf ON u.users_id = gf.users_id AND gf.from_who = 0
            LEFT JOIN FEEDBACK f ON gf.feedback_id = f.feedback_id
            WHERE r.ride_id = ?
            GROUP BY u.name, u.last_name, u.phone,
                     r.estimated_fare, r.estimated_distance, r.estimated_duration";

    $result = runSelect($sql, [$ride_id])[0];

    return [
        'customer_name' => $result['name'] . ' ' . $result['last_name'],
        'customer_phone' => $result['phone'],
        'customer_rating' => round($result['rating'], 1),
        'fare' => '$' . number_format($result['estimated_fare'], 2),
        'distance' => round($result['estimated_distance'], 1) . ' km',
        'eta' => round($result['estimated_duration']) . ' min'
    ];
}

function getDriverStats() {
    $driver_id=getCurrentDriverId();
    $period=$_GET['period'] ?? 'monthly';
    switch($period){
        case 'daily': $dateCond="CAST(r.pickup_time AS DATE)=CAST(GETDATE() AS DATE)"; break;
        case 'weekly': $dateCond="r.pickup_time>=DATEADD(week,-1,GETDATE())"; break;
        default: $dateCond="r.pickup_time>=DATEADD(month,-1,GETDATE())";
    }
    $query="SELECT COUNT(DISTINCT r.ride_id) AS total_rides,
                   ISNULL(SUM(p.amount),0) AS total_earnings,
                   ISNULL(AVG(CAST(f.rating AS FLOAT)),0) AS avg_rating
            FROM [RIDE] r
            LEFT JOIN [PAYMENT] p ON r.ride_id=p.ride_id
            LEFT JOIN [GIVEN_FEEDBACK] gf ON r.ride_id=gf.ride_id AND gf.driver_id=r.driver_id AND gf.from_who=0
            LEFT JOIN [FEEDBACK] f ON gf.feedback_id=f.feedback_id
            WHERE r.driver_id=? AND r.status='Completed' AND $dateCond";
    $res = runSelect($query, [$driver_id]);
    echo json_encode([
        'rides'=>$res[0]['total_rides']??0,
        'earnings'=>number_format($res[0]['total_earnings']??0,2),
        'rating'=>number_format($res[0]['avg_rating']??0,1)
    ]);
}

function getDriverFeedback() {
    $driver_id=getCurrentDriverId();
    $query="SELECT TOP 20 u.name+' '+u.last_name AS customer_name,f.comments,f.rating,r.dropoff_time AS feedback_date
            FROM [GIVEN_FEEDBACK] gf
            INNER JOIN [FEEDBACK] f ON gf.feedback_id=f.feedback_id
            INNER JOIN [USERS] u ON gf.users_id=u.users_id
            INNER JOIN [RIDE] r ON gf.ride_id=r.ride_id
            WHERE gf.driver_id=? AND gf.from_who=0
            ORDER BY r.dropoff_time DESC";
    $res = runSelect($query, [$driver_id]);
    $feedback=[];
    foreach($res as $row){
        $feedback[]=[
            'customer_name'=>$row['customer_name'],
            'comment'=>$row['comments'],
            'rating'=>$row['rating'],
            'date'=>$row['feedback_date']?date('M d, Y',strtotime($row['feedback_date'])):''
        ];
    }
    echo json_encode(['success'=>true,'feedback'=>$feedback]);
}

function getDriverInfoAPI() {
    $driver_id=getCurrentDriverId();
    $query="SELECT d.driver_id, d.status, u.name, u.last_name, u.email, u.phone, d.picture
            FROM [DRIVER] d INNER JOIN [USERS] u ON d.users_id=u.users_id
            WHERE d.driver_id=?";
    $res = runSelect($query, [$driver_id]);
    if($res){
        $info=$res[0];
        echo json_encode(['success'=>true,'driver'=>[
            'id'=>$info['driver_id'],
            'name'=>$info['name'].' '.$info['last_name'],
            'email'=>$info['email'],
            'phone'=>$info['phone'],
            'status'=>$info['status'],
            'picture'=>$info['picture']
        ]]);
    } else echo json_encode(['success'=>false,'error'=>'Driver not found']);
}

// Placeholder chat functions (requires CHAT_MESSAGES table)
function getChatMessages(){ echo json_encode(['success'=>true,'messages'=>[]]); }
function sendChatMessage(){ echo json_encode(['success'=>true,'message'=>'Message sent']); }
function setAvailability(){ echo json_encode(['success'=>true,'status'=>'A','message'=>'Availability updated']); }

?>

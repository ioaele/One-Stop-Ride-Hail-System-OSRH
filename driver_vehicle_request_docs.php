<?php
session_start();
require_once 'connect.php';   // <--- το αρχείο που μου έδειξες

setCorsHeaders();
header('Content-Type: application/json');

// 1. Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// 2. DB connection μέσω της Database κλάσης σου
try {
    $database = new Database();
    $pdo      = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database connection failed.'
        // 'debug' => $e->getMessage()
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // ================================
    // 3. Driver / Vehicle σύνδεση
    // ================================
    // Αν έχεις ήδη driver_id & vehicle_id μετά το login:
    $driverId  = $_SESSION['driver_id']  ?? null;
    $vehicleId = $_SESSION['vehicle_id'] ?? null;

    // Εδώ μπορείς να κάνεις INSERT/UPDATE για DRIVER & VEHICLE tables
    // χρησιμοποιώντας τα πεδία από $_POST['vehicle'], κλπ.
    // Π.χ. vehicle[service_type], vehicle[license_plate], ...
    // και να πάρεις $vehicleId αν το δημιουργείς τώρα.

    // ================================
    // 4. Φάκελοι για τα uploads
    // ================================
    $driverDocUploadBase  = __DIR__ . '/uploads/driver_docs/';
    $vehicleDocUploadBase = __DIR__ . '/uploads/vehicle_docs/';

    if (!is_dir($driverDocUploadBase)) {
        mkdir($driverDocUploadBase, 0777, true);
    }
    if (!is_dir($vehicleDocUploadBase)) {
        mkdir($vehicleDocUploadBase, 0777, true);
    }

    $errors        = [];
    $driverDocOK   = 0;
    $vehicleDocOK  = 0;

    // ==========================================
    // 5. DRIVER DOCS  -> insertDriverDoc sproc
    // ==========================================
    if (!empty($_POST['driver_docs']) && is_array($_POST['driver_docs'])) {
        $driverDocs  = $_POST['driver_docs'];
        $driverFiles = $_FILES['driver_docs'] ?? null;

        foreach ($driverDocs as $index => $doc) {
            // Πεδίο τύπου εγγράφου (1=ID/Passport, 2=Residence, 3=License, 6=CR κλπ)
            // μπορεί να έρχεται ως driver_docs[?][d_doc_type] ή για το πρώτο doc ως απλό d_doc_type.
            $docTypeCode = $doc['d_doc_type'] ?? null;
            if ($index == 0 && !$docTypeCode && isset($_POST['d_doc_type'])) {
                $docTypeCode = $_POST['d_doc_type'];
            }

            $docCode = $doc['doc_code']            ?? null;
            $pubDate = $doc['d_doc_publish_date']  ?? null;
            $expDate = $doc['d_doc_ex_date']       ?? null;

            if (empty($docTypeCode) || empty($docCode) || empty($pubDate)) {
                $errors[] = "Missing driver doc fields at index {$index}.";
                continue;
            }

            // Αρχεία: λόγω ονόματος driver_docs[0][image_pdf]
            $fileName = $driverFiles['name'][$index]['image_pdf']     ?? null;
            $tmpName  = $driverFiles['tmp_name'][$index]['image_pdf'] ?? null;
            $fileErr  = $driverFiles['error'][$index]['image_pdf']    ?? UPLOAD_ERR_NO_FILE;

            if ($fileErr !== UPLOAD_ERR_OK || !$tmpName) {
                $errors[] = "Driver doc file upload failed at index {$index}.";
                continue;
            }

            // Έλεγχος Criminal Record (τύπος 6) <= 1 μήνας
            if ((int)$docTypeCode === 6) {
                try {
                    $crDate      = new DateTime($pubDate);
                    $oneMonthAgo = (new DateTime())->modify('-1 month');
                    if ($crDate < $oneMonthAgo) {
                        $errors[] = "Criminal record certificate (driver doc index {$index}) must not be older than one month.";
                        continue;
                    }
                } catch (Exception $e) {
                    $errors[] = "Invalid CR publish date at driver doc index {$index}.";
                    continue;
                }
            }

            // Μεταφορά αρχείου
            $safeName = time() . '_d_' . $index . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $fileName);
            $target   = $driverDocUploadBase . $safeName;

            if (!move_uploaded_file($tmpName, $target)) {
                $errors[] = "Could not move driver doc file at index {$index}.";
                continue;
            }

            $imagePath = 'uploads/driver_docs/' . $safeName;

            // -----------------------------
            // CALL insertDriverDoc sproc
            // -----------------------------
            // CREATE PROCEDURE insertDriverDoc
            // (
            //   @doc_type nvarchar(50) NOT NULL,
            //   @doc_code nvarchar(50) NOT NULL,
            //   @d_doc_publish_date DATE NOT NULL,
            //   @d_doc_ex_date DATE NULL,
            //   @image_pdf nvarchar(max) NOT NULL
            // )
            $stmt = $pdo->prepare("EXEC insertDriverDoc ?, ?, ?, ?, ?");

            // Χρησιμοποιούμε positional params για να δουλέψει ωραία με ODBC
            $docTypeParam = (string)$docTypeCode; // αν ο πίνακας έχει text τύπους, μπορείς να κάνεις mapping
            $stmt->bindParam(1, $docTypeParam, PDO::PARAM_STR);
            $stmt->bindParam(2, $docCode,      PDO::PARAM_STR);
            $stmt->bindParam(3, $pubDate,      PDO::PARAM_STR);
            $stmt->bindParam(4, $expDate,      PDO::PARAM_STR);
            $stmt->bindParam(5, $imagePath,    PDO::PARAM_STR);

            $stmt->execute();
            $driverDocOK++;
        }
    }

    // ==========================================
    // 6. VEHICLE DOCS  -> insertVehicleDoc sproc
    // ==========================================
    if (!empty($_POST['vehicle_docs']) && is_array($_POST['vehicle_docs'])) {
        $vehicleDocs  = $_POST['vehicle_docs'];
        $vehicleFiles = $_FILES['vehicle_docs'] ?? null;

        foreach ($vehicleDocs as $index => $doc) {
            $vDocTypeCode = $doc['v_doc_type']          ?? null; // 1=Reg, 2=MOT, 3=Classification
            $vPubDate     = $doc['v_doc_publish_date']  ?? null;
            $vExpDate     = $doc['v_doc_exp_date']      ?? null;

            if (empty($vDocTypeCode) || empty($vPubDate) || empty($vExpDate)) {
                $errors[] = "Missing vehicle doc fields at index {$index}.";
                continue;
            }

            $fileName = $vehicleFiles['name'][$index]['image_pdf']     ?? null;
            $tmpName  = $vehicleFiles['tmp_name'][$index]['image_pdf'] ?? null;
            $fileErr  = $vehicleFiles['error'][$index]['image_pdf']    ?? UPLOAD_ERR_NO_FILE;

            if ($fileErr !== UPLOAD_ERR_OK || !$tmpName) {
                $errors[] = "Vehicle doc file upload failed at index {$index}.";
                continue;
            }

            $safeName = time() . '_v_' . $index . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $fileName);
            $target   = $vehicleDocUploadBase . $safeName;

            if (!move_uploaded_file($tmpName, $target)) {
                $errors[] = "Could not move vehicle doc file at index {$index}.";
                continue;
            }

            $filePath = 'uploads/vehicle_docs/' . $safeName;

            // -----------------------------
            // CALL insertVehicleDoc sproc
            // -----------------------------
            // CREATE PROCEDURE insertVehicleDoc
            // (
            //   @v_doc_type nvarchar(50) NOT NULL,
            //   @v_doc_exp_date date NOT NULL,
            //   @file nvarchar(max) NOT NULL,
            //   @v_doc_publish_date DATE NOT NULL
            // )
            $stmt = $pdo->prepare("EXEC insertVehicleDoc ?, ?, ?, ?");

            $vDocTypeParam = (string)$vDocTypeCode;
            $stmt->bindParam(1, $vDocTypeParam, PDO::PARAM_STR);
            $stmt->bindParam(2, $vExpDate,      PDO::PARAM_STR);
            $stmt->bindParam(3, $filePath,      PDO::PARAM_STR);
            $stmt->bindParam(4, $vPubDate,      PDO::PARAM_STR);

            $stmt->execute();
            $vehicleDocOK++;
        }
    }

    // ================================
    // 7. Commit & JSON response
    // ================================
    if (!empty($errors) && $driverDocOK === 0 && $vehicleDocOK === 0) {
        $pdo->rollBack();
        echo json_encode([
            'status'  => 'error',
            'message' => 'No document was saved.',
            'errors'  => $errors
        ]);
        exit;
    }

    $pdo->commit();

    $msg = "Request saved. Driver docs: {$driverDocOK}, Vehicle docs: {$vehicleDocOK}.";
    if (!empty($errors)) {
        echo json_encode([
            'status'  => 'success',
            'message' => $msg . ' Some errors occurred.',
            'errors'  => $errors
        ]);
    } else {
        echo json_encode([
            'status'  => 'success',
            'message' => $msg
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected server error.',
        'debug' => $e->getMessage()
    ]);
}

exit;

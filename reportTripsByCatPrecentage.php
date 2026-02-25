<?php
/**
 * PHP Script to Execute the '[eioann09].[ReportTripsByCategoryPercentage]' Stored Procedure
 * This script connects to SQL Server, runs the SP, and displays the results in a table.
 */

// 1. Configuration: Update these database credentials and details
$serverName = "mssql.cs.ucy.ac.cy";
$dbName     = "eioann09";
$dbUser     = "eioann09";
$dbPass     = "CQxPy3nG";
$storedProcName = "[eioann09].[ReportTripsByCategoryPercentage]";

// Array to hold the fetched report data
$reportData = [];
$errorMessage = null;

try {
    // 2. Establish Connection
    $dsn = "sqlsrv:server=$serverName;database=$dbName";
    $conn = new PDO($dsn, $dbUser, $dbPass);
    
    // Set PDO error mode to exception for robust error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Prepare and Execute the Stored Procedure Call
    // Use the {CALL ...} syntax for stored procedures without parameters
    $sql = "{CALL $storedProcName}";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // 4. Fetch the Results
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // 5. Error Handling
    $errorMessage = "Database Error: Failed to generate report. " . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    // Catch other general exceptions
    $errorMessage = "An unexpected error occurred: " . htmlspecialchars($e->getMessage());
} finally {
    // 6. Close Connection
    $conn = null;
    $stmt = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Type Percentage Report</title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS to ensure better table visibility and padding */
        .report-table th, .report-table td {
            padding: 12px 15px;
            text-align: left;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8 font-sans">

    <div class="max-w-4xl mx-auto bg-white p-6 md:p-10 rounded-xl shadow-lg">
        <h1 class="text-3xl font-bold text-indigo-700 mb-6 border-b pb-3">
            Trip Category Percentage Report
        </h1>
        <p class="text-gray-600 mb-8">
            This report shows the breakdown of all rides by service type, calculated directly by the SQL Stored Procedure `<?php echo $storedProcName; ?>`.
        </p>

        <?php if ($errorMessage): ?>
            <!-- Display Error Message -->
            <div role="alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md">
                <p class="font-bold">Execution Failed!</p>
                <p class="text-sm"><?php echo $errorMessage; ?></p>
            </div>

        <?php elseif (empty($reportData)): ?>
            <!-- Display No Data Message -->
            <div role="alert" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-md">
                <p class="font-bold">No Data Found</p>
                <p class="text-sm">The report ran successfully but returned no ride data. Check the RIDE table.</p>
            </div>
            
        <?php else: ?>
            <!-- Display Report Table -->
            <div class="overflow-x-auto">
                <table class="report-table min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-indigo-500 text-white">
                        <tr>
                            <th class="rounded-tl-lg">Ride Type</th>
                            <th class="">Total Rides</th>
                            <th class="rounded-tr-lg">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                        <tr class="border-b hover:bg-indigo-50 transition duration-150">
                            <td class="font-medium text-gray-800">
                                <?php echo htmlspecialchars($row['ride_type']); ?>
                            </td>
                            <td class="text-gray-600">
                                <?php echo htmlspecialchars(number_format($row['total rides'])); ?>
                            </td>
                            <td class="text-indigo-600 font-bold">
                                <!-- Format percentage to two decimal places -->
                                <?php echo htmlspecialchars(number_format($row['percentage'], 2)) . '%'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
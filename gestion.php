<?php
session_start(); // Starts the session to access user data and maintain state across requests.

// Redirects users who are not logged in as a 'Gestionnaire' (Manager) or if their user ID is missing.
// This ensures only authorized users can access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gestionnaire' || !isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirect to the login page if conditions are not met.
    exit(); // Stop script execution to prevent further processing for unauthorized users.
}

// Includes the database connection file. This file is expected to contain the logic for connecting to the database.
require_once 'db_connect.php';

// Retrieves the manager's building ID and username from the session variables.
// The user ID from the session is cast to an integer for security and type consistency.
$id_batiment_gestionnaire = (int)$_SESSION['user_id'];
$nom_gestionnaire = $_SESSION['username'];

// Retrieves the selected sensor's name from the GET parameters in the URL.
// If no sensor is selected (e.g., on initial page load), it defaults to an empty string.
$capteur_selectionne = isset($_GET['capteur']) ? $_GET['capteur'] : '';

// --- Defines the default time range for data retrieval: the last 24 hours ---
// 'date_fin' is set to the current date and time.
$date_fin = (new DateTime())->format('Y-m-d H:i:s');
// 'date_debut' is calculated by subtracting 24 hours from the current time.
$date_debut = (new DateTime())->modify('-24 hours')->format('Y-m-d H:i:s');


// --- Retrieves available sensors for the manager's building ---
$capteurs_disponibles = []; // Initializes an empty array to store available sensors.
// Prepares an SQL statement to fetch distinct sensor names, types, and units.
// It joins 'Capteur' (Sensor) and 'Salle' (Room) tables to filter by the manager's building ID.
$stmt_capteurs = mysqli_prepare($conn, "
    SELECT DISTINCT C.nom_capteur, C.type_capteur, C.Unite
    FROM Capteur C
    JOIN Salle S ON C.nom_salle = S.nom_salle
    WHERE S.id_batiment = ?
");

// Checks if the SQL statement was successfully prepared.
if ($stmt_capteurs) {
    mysqli_stmt_bind_param($stmt_capteurs, "i", $id_batiment_gestionnaire); // Binds the building ID parameter as an integer.
    mysqli_stmt_execute($stmt_capteurs); // Executes the prepared query.
    $result_capteurs = mysqli_stmt_get_result($stmt_capteurs); // Gets the result set from the executed query.
    $capteurs_disponibles = mysqli_fetch_all($result_capteurs, MYSQLI_ASSOC); // Fetches all results as an associative array.
    mysqli_free_result($result_capteurs); // Frees the memory associated with the result set.
    mysqli_stmt_close($stmt_capteurs); // Closes the prepared statement.
} else {
    // Logs any errors if the statement preparation failed. This is useful for debugging.
    error_log("Erreur de préparation de la requête des capteurs : " . mysqli_error($conn));
}


// --- Retrieves measurements for the selected sensor within the last 24 hours ---
$mesures = []; // Initializes an empty array to store raw measurements.
// Proceeds only if a sensor has been selected.
if ($capteur_selectionne) {
    // Prepares an SQL statement to fetch sensor values, timestamps, and units.
    // It joins 'Mesure' (Measurement) and 'Capteur' (Sensor) tables.
    // Data is ordered by 'date_heure' in ascending order for easier processing (aggregation) in JavaScript.
    $stmt_mesures = mysqli_prepare($conn, "
        SELECT Mesure.valeur, Mesure.date_heure, Capteur.Unite
        FROM Mesure
        JOIN Capteur ON Mesure.nom_capteur = Capteur.nom_capteur
        WHERE Mesure.nom_capteur = ? AND Mesure.date_heure BETWEEN ? AND ?
        ORDER BY Mesure.date_heure ASC
    ");

    // Checks if the SQL statement was successfully prepared.
    if ($stmt_mesures) {
        // Binds parameters: sensor name (string), start date (string), end date (string).
        mysqli_stmt_bind_param($stmt_mesures, "sss", $capteur_selectionne, $date_debut, $date_fin);
        mysqli_stmt_execute($stmt_mesures); // Executes the prepared query.
        $result_mesures = mysqli_stmt_get_result($stmt_mesures); // Gets the result set.
        $mesures = mysqli_fetch_all($result_mesures, MYSQLI_ASSOC); // Fetches all results as an associative array.
        mysqli_free_result($result_mesures); // Frees the memory associated with the result set.
        mysqli_stmt_close($stmt_mesures); // Closes the prepared statement.
    } else {
        // Logs any errors if the statement preparation failed.
        error_log("Erreur de préparation de la requête des mesures : " . mysqli_error($conn));
    }
}

// --- Aggregates measurements for the HTML table to show only one entry per minute ---
$final_mesures_for_table = []; // Initializes an empty array for aggregated table data.
if (!empty($mesures)) {
    $aggregated_temp = []; // Temporary array to hold sums and counts for aggregation.
    foreach ($mesures as $mesure) {
        $datetime = new DateTime($mesure['date_heure']);
        // Creates a unique key for each minute (e.g., 'YYYY-MM-DD HH:mm').
        $time_key = $datetime->format('Y-m-d H:i');

        // If this minute hasn't been encountered yet, initialize its aggregation data.
        if (!isset($aggregated_temp[$time_key])) {
            $aggregated_temp[$time_key] = [
                'sum' => 0,
                'count' => 0,
                'Unite' => $mesure['Unite'],
                'date_heure' => $time_key . ':00' // Sets seconds to 00 for display in the table.
            ];
        }
        // Adds the current measurement's value to the sum and increments the count for this minute.
        $aggregated_temp[$time_key]['sum'] += (float)$mesure['valeur'];
        $aggregated_temp[$time_key]['count']++;
    }

    // Converts aggregated sums/counts into averages and populates the final table array.
    foreach ($aggregated_temp as $key => $data) {
        $final_mesures_for_table[] = [
            'valeur' => number_format($data['sum'] / $data['count'], 2), // Formats the average to 2 decimal places.
            'Unite' => $data['Unite'],
            'date_heure' => $data['date_heure']
        ];
    }
    // Reverses the array to display the most recent measurements first in the table,
    // consistent with typical "latest first" display.
    $final_mesures_for_table = array_reverse($final_mesures_for_table);
}


// --- Retrieves statistics (average, min, max) by room and sensor in the building for the last 24 hours ---
$stats_salles = []; // Initializes an empty array for room statistics.
// Prepares an SQL statement to calculate statistics (average, minimum, maximum values).
// It joins 'Salle' (Room), 'Capteur' (Sensor), and 'Mesure' (Measurement) tables.
// Filters for the manager's building ID and the last 24 hours.
// Groups results by room, sensor name, type, and unit.
$stmt_stats = mysqli_prepare($conn, "
    SELECT
        Salle.nom_salle,
        Capteur.nom_capteur,
        Capteur.type_capteur,
        Capteur.Unite,
        AVG(Mesure.valeur) AS moyenne_valeur,
        MIN(Mesure.valeur) AS min_valeur,
        MAX(Mesure.valeur) AS max_valeur
    FROM Salle
    JOIN Capteur ON Salle.nom_salle = Capteur.nom_salle
    LEFT JOIN Mesure ON Capteur.nom_capteur = Mesure.nom_capteur
    WHERE Salle.id_batiment = ?
    AND Mesure.date_heure BETWEEN ? AND ? -- Filters for the last 24 hours.
    GROUP BY Salle.nom_salle, Capteur.nom_capteur, Capteur.type_capteur, Capteur.Unite
    ORDER BY Salle.nom_salle, Capteur.nom_capteur
");

// Checks if the SQL statement was successfully prepared.
if ($stmt_stats) {
    // Binds parameters: building ID (integer), start date (string), end date (string).
    mysqli_stmt_bind_param($stmt_stats, "iss", $id_batiment_gestionnaire, $date_debut, $date_fin);
    mysqli_stmt_execute($stmt_stats); // Executes the prepared query.
    $result_stats = mysqli_stmt_get_result($stmt_stats); // Gets the result set.
    // Loops through the results and organizes statistics by room name.
    while ($row = mysqli_fetch_assoc($result_stats)) {
        $stats_salles[$row['nom_salle']][] = $row;
    }
    mysqli_free_result($result_stats); // Frees the memory associated with the result set.
    mysqli_stmt_close($stmt_stats); // Closes the prepared statement.
} else {
    // Logs any errors if the statement preparation failed.
    error_log("Erreur de préparation de la requête des statistiques : " . mysqli_error($conn));
}

// Closes the database connection after all operations are complete.
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css">
    <!-- Includes the Chart.js library for creating interactive charts. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <!-- Displays the manager's name in the header. -->
        <h1>Panneau de Gestionnaire : <?php echo htmlspecialchars($nom_gestionnaire); ?></h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrateur'): // Shows Admin link only for Admins. ?>
                    <li><a href="admin.php">Administration</a></li>
                <?php endif; ?>
                <li><a href="gestion.php" class="active">Gestion</a></li>
                <li><a href="deconnexion.php">Déconnexion</a></li>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Mesures des Capteurs de Votre Bâtiment (24 dernières heures)</h2>
            <form action="gestion.php" method="GET">
                <label for="capteur">Sélectionner un Capteur :</label>
                <select name="capteur" id="capteur">
                    <option value="">-- Tous les capteurs --</option>
                    <?php foreach ($capteurs_disponibles as $capteur): ?>
                        <option value="<?php echo htmlspecialchars($capteur['nom_capteur']); ?>"
                            <?php echo ($capteur_selectionne == $capteur['nom_capteur']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($capteur['nom_capteur'] . " (" . $capteur['type_capteur'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Afficher les Mesures">
            </form>

            <?php if (!empty($capteur_selectionne)): ?>
                <h3>Historique des Mesures pour <?php echo htmlspecialchars($capteur_selectionne); ?></h3>
                <?php if (!empty($mesures)): // We still check if raw measurements exist for the chart ?>
                    <div style="width: 80%; margin: auto;">
                        <canvas id="graphiqueMesures"></canvas>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Valeur (Moyenne)</th> <!-- Updated column header to indicate average value. -->
                                <th>Unité</th>
                                <th>Date/Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($final_mesures_for_table as $mesure_aggregated): // Using aggregated data for the table ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mesure_aggregated['valeur']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure_aggregated['Unite']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure_aggregated['date_heure']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <script>
                        const ctx = document.getElementById('graphiqueMesures').getContext('2d');
                        // Raw data from PHP (already sorted ASC from the database query)
                        const mesuresRawData = <?php echo json_encode($mesures); ?>;

                        // Aggregates data to ensure unique HH:mm labels and average values for the chart
                        const aggregatedMesures = {};

                        mesuresRawData.forEach(mesure => {
                            const date = new Date(mesure.date_heure);
                            // Formats to HH:mm, which will be the unique key for aggregation
                            const timeKey = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                            // Initializes aggregation data for a new minute if not already present.
                            if (!aggregatedMesures[timeKey]) {
                                aggregatedMesures[timeKey] = {
                                    sum: 0,
                                    count: 0
                                };
                            }
                            // Adds the current measurement's value to the sum and increments the count.
                            aggregatedMesures[timeKey].sum += parseFloat(mesure.valeur);
                            aggregatedMesures[timeKey].count++;
                        });

                        // Prepares labels and values for Chart.js from the aggregated data.
                        // Sorts labels chronologically to ensure correct display on the X-axis.
                        const labels = Object.keys(aggregatedMesures).sort((a, b) => {
                            // Splits HH:mm string into hours and minutes for numerical comparison.
                            const [hA, mA] = a.split(':').map(Number);
                            const [hB, mB] = b.split(':').map(Number);
                            if (hA !== hB) return hA - hB; // Sort by hour first.
                            return mA - mB; // Then sort by minute.
                        });

                        const valeurs = labels.map(timeKey => {
                            // Calculates the average for each aggregated time point.
                            return aggregatedMesures[timeKey].sum / aggregatedMesures[timeKey].count;
                        });

                        // Gets the unit from the first available measurement (assuming units are consistent for a selected sensor).
                        const unite = mesuresRawData.length > 0 ? mesuresRawData[0].Unite : '';

                        // Initializes a new Chart.js line chart.
                        new Chart(ctx, {
                            type: 'line', // Specifies the chart type as a line chart.
                            data: {
                                labels: labels, // X-axis labels (aggregated times).
                                datasets: [{
                                    label: `Valeur (${unite})`, // Dataset label, including the unit.
                                    data: valeurs, // Y-axis data (aggregated values).
                                    borderColor: 'rgb(75, 192, 192)', // Line color.
                                    tension: 0.1, // Smoothness of the line (0 for straight lines).
                                    fill: false // Do not fill the area under the line.
                                }]
                            },
                            options: {
                                responsive: true, // Makes the chart responsive to container size changes.
                                plugins: {
                                    title: {
                                        display: true, // Displays the chart title.
                                        text: 'Évolution des Mesures du Capteur' // Chart title text.
                                    }
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true, // Displays the X-axis title.
                                            text: 'Heure' // X-axis title text.
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true, // Displays the Y-axis title.
                                            text: 'Valeur' // Y-axis title text.
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <p class="message info">Aucune mesure trouvée pour ce capteur dans les 24 dernières heures.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="message info">Sélectionnez un capteur pour afficher ses mesures.</p>
            <?php endif; ?>

            <hr>

            <h2>Statistiques des Salles de Votre Bâtiment (24 dernières heures)</h2>
            <?php if (!empty($stats_salles)): ?>
                <?php foreach ($stats_salles as $nom_salle => $capteurs_stats): ?>
                    <h3>Salle : <?php echo htmlspecialchars($nom_salle); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Capteur</th>
                                <th>Type</th>
                                <th>Unité</th>
                                <th>Moyenne</th>
                                <th>Min</th>
                                <th>Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($capteurs_stats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['nom_capteur']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['type_capteur']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['Unite']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format(isset($stat['moyenne_valeur']) ? $stat['moyenne_valeur'] : 0, 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format(isset($stat['min_valeur']) ? $stat['min_valeur'] : 0, 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format(isset($stat['max_valeur']) ? $stat['max_valeur'] : 0, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="message info">Aucune statistique de salle trouvée pour ce bâtiment dans les 24 dernières heures.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>


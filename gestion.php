<?php
session_start(); // Starts the session to access user data.

// Redirects users who are not logged in as a 'Gestionnaire' or if user ID is missing.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gestionnaire' || !isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirect to login page.
    exit(); // Stop script execution.
}

// Includes the database connection file.
require_once 'db_connect.php';

// Retrieves manager's building ID and username from session.
$id_batiment_gestionnaire = (int)$_SESSION['user_id'];
$nom_gestionnaire = $_SESSION['username'];

// Retrieves the selected sensor from GET parameters.
$capteur_selectionne = isset($_GET['capteur']) ? $_GET['capteur'] : '';

// --- Defines the default time range: the last 24 hours ---
$date_fin = (new DateTime())->format('Y-m-d H:i:s'); // Current date and time.
$date_debut = (new DateTime())->modify('-24 hours')->format('Y-m-d H:i:s'); // 24 hours ago.


// --- Retrieves available sensors for the manager's building ---
$capteurs_disponibles = [];
// Prepares a SQL statement to fetch distinct sensor names, types, and units.
$stmt_capteurs = mysqli_prepare($conn, "
    SELECT DISTINCT C.nom_capteur, C.type_capteur, C.Unite
    FROM Capteur C
    JOIN Salle S ON C.nom_salle = S.nom_salle
    WHERE S.id_batiment = ?
");

if ($stmt_capteurs) {
    mysqli_stmt_bind_param($stmt_capteurs, "i", $id_batiment_gestionnaire); // Binds the building ID parameter.
    mysqli_stmt_execute($stmt_capteurs); // Executes the query.
    $result_capteurs = mysqli_stmt_get_result($stmt_capteurs); // Gets the result set.
    $capteurs_disponibles = mysqli_fetch_all($result_capteurs, MYSQLI_ASSOC); // Fetches all results as associative array.
    mysqli_free_result($result_capteurs); // Frees the result set.
    mysqli_stmt_close($stmt_capteurs); // Closes the prepared statement.
} else {
    error_log("Erreur de préparation de la requête des capteurs : " . mysqli_error($conn)); // Logs any errors.
}


// --- Retrieves measurements for the selected sensor within the last 24 hours ---
$mesures = [];
if ($capteur_selectionne) {
    // Prepares a SQL statement to fetch sensor values, timestamps, and units.
    $stmt_mesures = mysqli_prepare($conn, "
        SELECT Mesure.valeur, Mesure.date_heure, Capteur.Unite
        FROM Mesure
        JOIN Capteur ON Mesure.nom_capteur = Capteur.nom_capteur
        WHERE Mesure.nom_capteur = ? AND Mesure.date_heure BETWEEN ? AND ?
        ORDER BY Mesure.date_heure DESC
    ");

    if ($stmt_mesures) {
        mysqli_stmt_bind_param($stmt_mesures, "sss", $capteur_selectionne, $date_debut, $date_fin); // Binds parameters (sensor name, start date, end date).
        mysqli_stmt_execute($stmt_mesures); // Executes the query.
        $result_mesures = mysqli_stmt_get_result($stmt_mesures); // Gets the result set.
        $mesures = mysqli_fetch_all($result_mesures, MYSQLI_ASSOC); // Fetches all results.
        mysqli_free_result($result_mesures); // Frees the result set.
        mysqli_stmt_close($stmt_mesures); // Closes the prepared statement.
    } else {
        error_log("Erreur de préparation de la requête des mesures : " . mysqli_error($conn)); // Logs any errors.
    }
}

// --- Retrieves statistics (average, min, max) by room and sensor in the building for the last 24 hours ---
$stats_salles = [];
// Prepares a SQL statement to calculate statistics.
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

if ($stmt_stats) {
    mysqli_stmt_bind_param($stmt_stats, "iss", $id_batiment_gestionnaire, $date_debut, $date_fin); // Binds parameters.
    mysqli_stmt_execute($stmt_stats); // Executes the query.
    $result_stats = mysqli_stmt_get_result($stmt_stats); // Gets the result set.
    while ($row = mysqli_fetch_assoc($result_stats)) {
        $stats_salles[$row['nom_salle']][] = $row; // Organizes statistics by room.
    }
    mysqli_free_result($result_stats); // Frees the result set.
    mysqli_stmt_close($stmt_stats); // Closes the prepared statement.
} else {
    error_log("Erreur de préparation de la requête des statistiques : " . mysqli_error($conn)); // Logs any errors.
}

// Closes the database connection.
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css"> <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> </head>
<body>
    <header>
        <h1>Panneau de Gestionnaire : <?php echo htmlspecialchars($nom_gestionnaire); ?></h1> <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrateur'): // Shows Admin link only for Admins. ?>
                    <li><a href="admin.php">Administration</a></li>
                <?php endif; ?>
                <li><a href="gestion.php" class="active">Gestion</a></li> <li><a href="deconnexion.php">Déconnexion</a></li>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Mesures des Capteurs de Votre Bâtiment (24 dernières heures)</h2> <form action="gestion.php" method="GET">
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
                <?php if (!empty($mesures)): ?>
                    <div style="width: 80%; margin: auto;">
                        <canvas id="graphiqueMesures"></canvas> </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Valeur</th>
                                <th>Unité</th>
                                <th>Date/Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mesures as $mesure): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mesure['valeur']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure['Unite']); ?></td>
                                    <td><?php echo htmlspecialchars($mesure['date_heure']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <script>
                        const ctx = document.getElementById('graphiqueMesures').getContext('2d');
                        // Reverses data for chronological display on the graph.
                        const mesuresData = <?php echo json_encode(array_reverse($mesures)); ?>;
                        const labels = mesuresData.map(m => new Date(m.date_heure).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}));
                        const valeurs = mesuresData.map(m => parseFloat(m.valeur)); // Converts values to numbers for graphing.
                        const unite = mesuresData.length > 0 ? mesuresData[0].Unite : '';

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: `Valeur (${unite})`,
                                    data: valeurs,
                                    borderColor: 'rgb(75, 192, 192)',
                                    tension: 0.1,
                                    fill: false
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Évolution des Mesures du Capteur'
                                    }
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Heure'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Valeur'
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <p class="message info">Aucune mesure trouvée pour ce capteur dans les 24 dernières heures.</p> <?php endif; ?>
            <?php else: ?>
                <p class="message info">Sélectionnez un capteur pour afficher ses mesures.</p>
            <?php endif; ?>

            <hr>

            <h2>Statistiques des Salles de Votre Bâtiment (24 dernières heures)</h2> <?php if (!empty($stats_salles)): ?>
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
                <p class="message info">Aucune statistique de salle trouvée pour ce bâtiment dans les 24 dernières heures.</p> <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>

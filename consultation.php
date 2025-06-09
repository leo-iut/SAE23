<?php
session_start(); // Start the session to access user role information for navigation.
require_once 'db_connect.php'; // Include the database connection file.

// Initialize an empty array to store the latest sensor measurements.
$derniere_mesures = [];

// SQL query to retrieve the latest measurement for each sensor.
// It joins Mesure, Capteur, Salle, and Batiment tables to get full details.
// The subquery 'WHERE M.id_mesure IN (SELECT MAX(id_mesure) FROM Mesure GROUP BY nom_capteur)'
// ensures that only the most recent measurement for each unique sensor is selected.
// Results are ordered by building, room, and then sensor name for better readability.
$sql = "
    SELECT
        M.nom_capteur,
        M.valeur,
        M.date_heure,
        C.type_capteur,
        C.Unite,
        S.nom_salle,
        B.nom_batiment
    FROM sae23.Mesure AS M
    INNER JOIN sae23.Capteur AS C ON M.nom_capteur = C.nom_capteur
    INNER JOIN sae23.Salle AS S ON C.nom_salle = S.nom_salle
    INNER JOIN sae23.Batiment AS B ON S.id_batiment = B.id_batiment
    WHERE M.id_mesure IN (
        SELECT MAX(id_mesure)
        FROM Mesure
        GROUP BY nom_capteur
    )
    ORDER BY B.nom_batiment, S.nom_salle, C.nom_capteur;
";
$result = mysqli_query($conn, $sql); // Execute the SQL query.

// Check if any rows were returned from the query.
if (mysqli_num_rows($result) > 0) {
    // Loop through each row and add it to the $derniere_mesures array.
    while($row = mysqli_fetch_assoc($result)) {
        $derniere_mesures[] = $row;
    }
}
mysqli_free_result($result); // Free up memory used by the query result.
mysqli_close($conn); // Close the database connection.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation - Plateforme IoT SAÉ23</title>
    <link rel="stylesheet" href="style.css">
	<meta http-equiv="refresh" content="60">
</head>
<body>
    <header>
        <h1>Dernières Lectures des Capteurs</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php" class="active">Consultation</a></li>
                <?php if (isset($_SESSION['role'])): // Check if a user is logged in. ?>
                    <?php if ($_SESSION['role'] === 'Administrateur'): // Show admin link if role is Administrator. ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php elseif ($_SESSION['role'] === 'Gestionnaire'): // Show manager link if role is Manager. ?>
                        <li><a href="gestion.php">Gestion</a></li>
                    <?php endif; ?>
                    <li><a href="deconnexion.php">Déconnexion</a></li>
                <?php else: // If no user is logged in, show the login link. ?>
                    <li><a href="connexion.php">Connexion</a></li>
                <?php endif; ?>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Données Actuelles des Capteurs (Tous Bâtiments)</h2>
            <?php if (!empty($derniere_mesures)): // Check if there are any sensor data to display. ?>
                <table>
                    <thead>
                        <tr>
                            <th>Bâtiment</th>
                            <th>Salle</th>
                            <th>Capteur</th>
                            <th>Type</th>
                            <th>Dernière Valeur</th>
                            <th>Unité</th>
                            <th>Dernière Mise à Jour</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniere_mesures as $mesure): // Loop through each sensor measurement. ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mesure['nom_batiment']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['nom_salle']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['nom_capteur']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['type_capteur']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['valeur']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['Unite']); ?></td>
                                <td><?php echo htmlspecialchars($mesure['date_heure']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: // Display a message if no sensor data is available. ?>
                <p class="message info">Aucune donnée de capteur disponible pour le moment.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>

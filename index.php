<?php
session_start(); // Start the session to check if a user is logged in and what their role is.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Plateforme IoT SAÉ23</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Bienvenue sur la Plateforme de Surveillance IoT</h1>
        <nav>
            <ul>
                <li><a href="index.php" class="active">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <?php if (isset($_SESSION['role'])): // Check if a user is logged in. ?>
                    <?php if ($_SESSION['role'] === 'Administrateur'): // If the role is Administrator, show the 'Administration' link. ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php elseif ($_SESSION['role'] === 'Gestionnaire'): // If the role is Manager, show the 'Gestion' link. ?>
                        <li><a href="gestion.php">Gestion</a></li>
                    <?php endif; ?>
                    <li><a href="deconnexion.php">Déconnexion</a></li> <?php else: // If no user is logged in, show the 'Connexion' link. ?>
                    <li><a href="connexion.php">Connexion</a></li>
                <?php endif; ?>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>À propos de ce projet</h2>
            <p>Cette plateforme a été développée dans le cadre de la SAÉ23 de l'IUT de Blagnac. Elle vise à visualiser les données de capteurs répartis dans les bâtiments de l'IUT.</p>
            <p>Elle intègre une chaîne de traitement des données via Docker (Mosquitto, Node-RED, InfluxDB, Grafana) et un site web dynamique avec une base de données MySQL.</p>
        </section>

        <section>
            <h2>Nos Bâtiments et Salles Équipées</h2>
            <?php
            require_once 'db_connect.php'; // Connect to the database.

            // SQL query to get information about buildings and rooms.
            // It uses a LEFT JOIN to include buildings even if they have no rooms.
            // Results are ordered by building name and then room name.
            $sql = "SELECT B.nom_batiment, S.nom_salle, S.type, S.capacite
                            FROM sae23.Batiment AS B
                            LEFT JOIN sae23.Salle AS S ON B.id_batiment = S.id_batiment
                            ORDER BY B.nom_batiment, S.nom_salle";
            $result = mysqli_query($conn, $sql); // Execute the query.

            // Check if there are any results.
            if (mysqli_num_rows($result) > 0) {
                echo "<table>"; // Start HTML table.
                echo "<thead><tr><th>Bâtiment</th><th>Salle</th><th>Type</th><th>Capacité</th></tr></thead>"; // Table header.
                echo "<tbody>"; // Table body.
                while($row = mysqli_fetch_assoc($result)) { // Loop through each row of data.
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_batiment']) . "</td>"; // Display building name.
                    echo "<td>" . htmlspecialchars($row['nom_salle']) . "</td>"; // Display room name.
                    echo "<td>" . htmlspecialchars($row['type']) . "</td>"; // Display room type.
                    echo "<td>" . htmlspecialchars($row['capacite']) . "</td>"; // Display room capacity.
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>"; // End HTML table.
            } else {
                echo "<p>Aucun bâtiment ou salle trouvé dans la base de données.</p>"; // Message if no data.
            }

            mysqli_close($conn); // Close the database connection.
            ?>
        </section>

        <section>
            <h2>Mentions Légales</h2>
            <p>Projet réalisé par des étudiants du Département Réseaux et Télécommunications de l'IUT de Blagnac.</p>
            <p>Contact : khalid.massaoudi@univ-tlse2.fr</p>
            <p>&copy; <?php echo date("Y"); ?> IUT de Blagnac. Tous droits réservés.</p>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>

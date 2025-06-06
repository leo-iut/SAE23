<?php
session_start();
// This checks if a user is logged in and if their role is 'Administrateur'.
// If not, they are redirected to the login page for security.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrateur') {
    header("Location: connexion.php");
    exit();
}

// Connect to the database using the 'db_connect.php' file.
require_once 'db_connect.php';

// Initialize a variable to store messages (success or error).
$message = '';

// Handle adding or deleting Buildings.
if (isset($_POST['ajouter_batiment'])) {
    $nom_batiment = $_POST['nom_batiment'];
    $nom_gest = $_POST['nom_gest'];
    $mdp_gest = $_POST['mdp_gest']; // IMPORTANT: Passwords should be hashed in production!

    // Prepare an SQL statement to insert a new building.
    $stmt = mysqli_prepare($conn, "INSERT INTO sae23.Batiment (nom_batiment, NomGest, MdpGest) VALUES (?, ?, ?)");
    // Bind parameters to prevent SQL injection. 'sss' means three strings.
    mysqli_stmt_bind_param($stmt, "sss", $nom_batiment, $nom_gest, $mdp_gest);
    // Execute the statement and set a success/error message.
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Bâtiment ajouté avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur d'ajout de bâtiment : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt); // Close the statement.
} elseif (isset($_POST['supprimer_batiment'])) {
    $id_batiment = $_POST['id_batiment_delete'];
    // NOTE: In a real application, you should handle dependencies (e.g., rooms, sensors, measurements)
    // before deleting a building to prevent data inconsistencies (e.g., set foreign keys to NULL or use CASCADE delete).
    // For this example, we'll just attempt to delete the building directly.
    $stmt = mysqli_prepare($conn, "DELETE FROM Batiment WHERE id_batiment = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_batiment); // 'i' for integer.
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Bâtiment supprimé avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur de suppression de bâtiment : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt);
}

// Handle adding or deleting Rooms (Salles).
if (isset($_POST['ajouter_salle'])) {
    $nom_salle = $_POST['nom_salle'];
    $capacite = $_POST['capacite'];
    $type = $_POST['type_salle'];
    $id_batiment = $_POST['id_batiment_salle'];

    $stmt = mysqli_prepare($conn, "INSERT INTO Salle (nom_salle, capacite, type, id_batiment) VALUES (?, ?, ?, ?)");
    // 'sisi' means string, integer, string, integer.
    mysqli_stmt_bind_param($stmt, "sisi", $nom_salle, $capacite, $type, $id_batiment);
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Salle ajoutée avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur d'ajout de salle : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt);
} elseif (isset($_POST['supprimer_salle'])) {
    $nom_salle = $_POST['nom_salle_delete'];
    // NOTE: Manage dependencies (sensors, measurements) before deleting a room.
    $stmt = mysqli_prepare($conn, "DELETE FROM Salle WHERE nom_salle = ?");
    mysqli_stmt_bind_param($stmt, "s", $nom_salle);
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Salle supprimée avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur de suppression de salle : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt);
}

// Handle adding or deleting Sensors (Capteurs).
if (isset($_POST['ajouter_capteur'])) {
    $nom_capteur = $_POST['nom_capteur'];
    $type_capteur = $_POST['type_capteur'];
    $unite = $_POST['unite'];
    $nom_salle_capteur = $_POST['nom_salle_associee'];

    $stmt = mysqli_prepare($conn, "INSERT INTO Capteur (nom_capteur, type_capteur, Unite, nom_salle) VALUES (?, ?, ?, ?)");
    // 'ssss' means four strings.
    mysqli_stmt_bind_param($stmt, "ssss", $nom_capteur, $type_capteur, $unite, $nom_salle_capteur);
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Capteur ajouté avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur d'ajout de capteur : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt);
} elseif (isset($_POST['supprimer_capteur'])) {
    $nom_capteur = $_POST['nom_capteur_delete'];
    // NOTE: Manage dependencies (measurements) before deleting a sensor.
    $stmt = mysqli_prepare($conn, "DELETE FROM Capteur WHERE nom_capteur = ?");
    mysqli_stmt_bind_param($stmt, "s", $nom_capteur);
    if (mysqli_stmt_execute($stmt)) { $message = "<p class='message success'>Capteur supprimé avec succès !</p>"; }
    else { $message = "<p class='message error'>Erreur de suppression de capteur : " . mysqli_error($conn) . "</p>"; }
    mysqli_stmt_close($stmt);
}

// Fetch data to populate dropdown lists (for deletion or association).
$result_buildings = mysqli_query($conn, "SELECT id_batiment, nom_batiment FROM Batiment");
$buildings = mysqli_fetch_all($result_buildings, MYSQLI_ASSOC);
mysqli_free_result($result_buildings); // Free memory.

$result_salles = mysqli_query($conn, "SELECT nom_salle FROM Salle");
$salles = mysqli_fetch_all($result_salles, MYSQLI_ASSOC);
mysqli_free_result($result_salles);

$result_capteurs = mysqli_query($conn, "SELECT nom_capteur FROM Capteur");
$capteurs = mysqli_fetch_all($result_capteurs, MYSQLI_ASSOC);
mysqli_free_result($result_capteurs);


mysqli_close($conn); // Close the database connection.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - SAÉ23 IoT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Panneau d'Administration</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="consultation.php">Consultation</a></li>
                <li><a href="admin.php" class="active">Administration</a></li>
                <li><a href="gestion.php">Gestion</a></li>
                <li><a href="deconnexion.php">Déconnexion</a></li>
                <li><a href="gestion_projet.php">Gestion Projet</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Actions Administratives</h2>
            <?php echo $message; ?>

            <h3>Ajouter/Supprimer un Bâtiment</h3>
            <form action="admin.php" method="POST">
                <label for="nom_batiment">Nom du Bâtiment :</label>
                <input type="text" id="nom_batiment" name="nom_batiment" required>
                <label for="nom_gest">Identifiant Gestionnaire :</label>
                <input type="text" id="nom_gest" name="nom_gest" required>
                <label for="mdp_gest">Mot de passe Gestionnaire :</label>
                <input type="password" id="mdp_gest" name="mdp_gest" required>
                <input type="submit" name="ajouter_batiment" value="Ajouter Bâtiment">
            </form>
            <form action="admin.php" method="POST">
                <label for="id_batiment_delete">Supprimer Bâtiment :</label>
                <select name="id_batiment_delete" id="id_batiment_delete" required>
                    <?php foreach ($buildings as $b) { echo "<option value='{$b['id_batiment']}'>" . htmlspecialchars($b['nom_batiment']) . "</option>"; } ?>
                </select>
                <input type="submit" name="supprimer_batiment" value="Supprimer Bâtiment">
            </form>

            <hr>

            <h3>Ajouter/Supprimer une Salle</h3>
            <form action="admin.php" method="POST">
                <label for="nom_salle">Nom de la Salle :</label>
                <input type="text" id="nom_salle" name="nom_salle" required>
                <label for="capacite">Capacité :</label>
                <input type="number" id="capacite" name="capacite" required>
                <label for="type_salle">Type :</label>
                <input type="text" id="type_salle" name="type_salle" required>
                <label for="id_batiment_salle">Bâtiment :</label>
                <select name="id_batiment_salle" id="id_batiment_salle" required>
                    <?php foreach ($buildings as $b) { echo "<option value='{$b['id_batiment']}'>" . htmlspecialchars($b['nom_batiment']) . "</option>"; } ?>
                </select>
                <input type="submit" name="ajouter_salle" value="Ajouter Salle">
            </form>
            <form action="admin.php" method="POST">
                <label for="nom_salle_delete">Supprimer Salle :</label>
                <select name="nom_salle_delete" id="nom_salle_delete" required>
                    <?php foreach ($salles as $s) { echo "<option value='" . htmlspecialchars($s['nom_salle']) . "'>" . htmlspecialchars($s['nom_salle']) . "</option>"; } ?>
                </select>
                <input type="submit" name="supprimer_salle" value="Supprimer Salle">
            </form>

            <hr>

            <h3>Ajouter/Supprimer un Capteur</h3>
            <form action="admin.php" method="POST">
                <label for="nom_capteur">Nom du Capteur :</label>
                <input type="text" id="nom_capteur" name="nom_capteur" required>
                <label for="type_capteur">Type de Capteur :</label>
                <input type="text" id="type_capteur" name="type_capteur" required>
                <label for="unite">Unité :</label>
                <input type="text" id="unite" name="unite" required>
                <label for="nom_salle_associee">Salle :</label>
                <select name="nom_salle_associee" id="nom_salle_associee" required>
                    <?php foreach ($salles as $s) { echo "<option value='" . htmlspecialchars($s['nom_salle']) . "'>" . htmlspecialchars($s['nom_salle']) . "</option>"; } ?>
                </select>
                <input type="submit" name="ajouter_capteur" value="Ajouter Capteur">
            </form>
            <form action="admin.php" method="POST">
                <label for="nom_capteur_delete">Supprimer Capteur :</label>
                <select name="nom_capteur_delete" id="nom_capteur_delete" required>
                    <?php foreach ($capteurs as $c) { echo "<option value='" . htmlspecialchars($c['nom_capteur']) . "'>" . htmlspecialchars($c['nom_capteur']) . "</option>"; } ?>
                </select>
                <input type="submit" name="supprimer_capteur" value="Supprimer Capteur">
            </form>
        </section>
    </main>

    <footer>
        <p>Projet SAÉ23 - IUT de Blagnac</p>
    </footer>
</body>
</html>

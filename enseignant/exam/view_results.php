<?php
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if ($_SESSION['user_role'] !== 'Enseignant') {
        header("Location: ../auth/login.php");
        exit();
    }

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Consulter les résultats</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f4f8;
        }

        .navbar {
            background-color: #1a365d;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 250px;
            background-color: white;
            padding: 2rem;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background-color: #4a5568;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .results-header {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .results-table {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .results-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .results-table th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1a365d;
        }

        .results-table tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-validated {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-pending {
            background-color: #feebc8;
            color: #744210;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-view {
            background-color: #1a365d;
            color: white;
        }

        .btn-edit {
            background-color: #4a90e2;
            color: white;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #4a5568;
            font-size: 0.875rem;
        }

        .export-btn {
            background-color: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
            margin-left: auto;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ExamMaster</div>
        <a href="logout.php" class="btn btn-view">Déconnexion</a>
    </nav>

    <div class="container">
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-icon">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='white' d='M12 4a4 4 0 014 4 4 4 0 01-4 4 4 4 0 01-4-4 4 4 0 014-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4z'/%3E%3C/svg%3E" 
                        alt="Profile"
                        style="width: 40px; height: 40px;">
                </div>
                <h2>Enseignant</h2>
            </div>
            <div class="nav-menu">
                <a href="../dashboard.php" style="text-decoration: none; color: #1a365d; display: block; padding: 0.5rem 0;">← Retour au tableau de bord</a>
            </div>
        </div>

        <div class="main-content">
            <div class="results-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1>Résultats des examens</h1>
                    <a href="#" class="export-btn">Exporter en Excel</a>
                </div>
                <div class="search-bar">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Rechercher par nom d'étudiant ou examen..."
                        value=""
                    >
                </div>
            </div>

            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-value">85%</div>
                    <div class="stat-label">Moyenne générale</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">45</div>
                    <div class="stat-label">Examens passés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">38</div>
                    <div class="stat-label">Examens validés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">7</div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>

            <div class="results-table">
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Examen</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                            <tr>
                                <td></td>
                                <td>/td>
                                <td></td>
                                <td>%</td>
                                <td>
                                    <span class="status-badge">
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="" class="btn btn-view">Voir</a>
                                    <a href="" class="btn btn-edit">Modifier</a>
                                </td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Handle search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('input', (e) => {
            const searchValue = e.target.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('search', searchValue);
            window.location.href = currentUrl.toString();
        });
    </script>
</body>
</html>
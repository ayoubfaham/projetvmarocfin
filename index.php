<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/city_image_helper.php';

// Tableau centralisé des villes populaires
$popular = ['casablanca', 'marrakech', 'tanger'];

try {
    $cities = $pdo->query("SELECT * FROM villes")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

if (empty($cities)) { echo '<div style="color:red;text-align:center;">Aucune ville trouvée en base de données.</div>'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Maroc Authentique | Découvrez les trésors du Maroc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Découvrez les plus belles destinations du Maroc et planifiez votre voyage idéal">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        :root {
            --primary-color: #2D2926;
            --secondary-color: #555555;
            --accent-color: #8B7355;
            --light-color: #F5F5F5;
            --text-color: #333333;
            --border-color: #E0E0E0;
            --footer-bg: #2D2926;
            --white: #FFFFFF;
            --transition: all 0.3s ease;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, .destination-item, .destination-item p, .destination-meta, .destination-address, .destination-rating {
            font-size: 0.91rem;
        }

        .hero p, .section-header p {
            font-size: 1rem;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
            background-color: var(--white);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Style */
        header.hero-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            background: transparent;
            box-shadow: none;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 3vw;
            height: 84px;
            background: transparent;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-img {
            height: 72px !important;
            width: auto;
            display: block;
        }
        
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-left: 10px;
        }
        
        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-btn {
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
        }
        
        .btn-outline {
            background: #222;
            color: #fff;
            border: none;
        }
        
        .btn-solid {
            background: var(--accent-color);
            color: var(--white);
            border: 1px solid var(--accent-color);
        }

        .nav-menu {
            display: flex;
            gap: 38px;
            list-style: none;
            margin: 0 0 0 48px;
            padding: 0;
            flex: 1;
            justify-content: center;
        }
        .nav-menu li a {
            color: #222;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 500;
            font-size: 1.13rem;
            letter-spacing: 0.5px;
            text-decoration: none;
            padding-bottom: 2px;
            border-bottom: 2px solid transparent;
            transition: color .2s, border .2s;
        }
        .nav-menu li a.active, .nav-menu li a:hover {
            color: #bfa14a;
            border-bottom: 2px solid #e9cba7;
        }

        /* Hero Section */
        .hero {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('../vmaroc1.png') center/cover no-repeat;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg,rgba(30,24,18,0.55) 0%,rgba(30,24,18,0.18) 60%,rgba(30,24,18,0.01) 100%);
            z-index: 1;
        }

        .hero-content.premium {
            position: absolute;
            left: 50%;
            bottom: 60px;
            transform: translateX(-50%);
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 32px;
            z-index: 2;
        }

        .hero-content.premium h1, .hero-content.premium .hero-sub, .hero-search input, .btn-cta {
            font-family: 'Montserrat', sans-serif !important;
        }

        .hero-content.premium h1 {
            font-size: 2.6rem;
            font-weight: 900;
            margin-bottom: 0;
            color: #fff;
            letter-spacing: 1.2px;
            text-shadow: 0 10px 48px #000c, 0 2px 0 #fff2;
            line-height: 1.08;
        }

        .hero-content.premium .hero-sub {
            font-size: 1.1rem;
            font-weight: 500;
            color: #e9cba7;
            margin-bottom: 0;
            text-shadow: 0 4px 18px #000a;
            line-height: 1.3;
        }

        .hero-search {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.10);
            border-radius: 32px;
            box-shadow: 0 2px 16px #0002;
            padding: 6px 10px 6px 22px;
            margin-bottom: 0;
            width: 100%;
            max-width: 340px;
            backdrop-filter: blur(2px);
        }

        .hero-search input {
            border: none;
            background: transparent;
            color: #fff;
            font-size: 1.13rem;
            outline: none;
            flex: 1;
            padding: 10px 0;
        }

        .hero-search input::placeholder {
            color: #f3e9d1;
            opacity: 1;
            font-weight: 400;
        }

        .hero-search button {
            background: none;
            border: none;
            color: #e9cba7;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0 12px;
            transition: color .2s;
        }

        .hero-search button:hover {
            color: #fff;
        }

        .btn-cta {
            display: inline-block;
            background: #e9cba7;
            color: #222;
            font-weight: 700;
            font-size: 1.18rem;
            border-radius: 32px;
            padding: 14px 38px;
            text-decoration: none;
            box-shadow: 0 4px 18px #0002;
            border: none;
            transition: background .2s, color .2s, box-shadow .2s;
            margin-top: 0;
            letter-spacing: 0.5px;
        }

        .btn-cta:hover {
            background: #d1b48a;
            color: #222;
            box-shadow: 0 8px 32px #e9cba755;
        }

        .hero-socials.premium {
            position: absolute;
            left: 2vw;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .hero-socials.premium a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            background: rgba(255,255,255,0.18);
            border-radius: 50%;
            color: #e9cba7;
            font-size: 1.7rem;
            box-shadow: 0 2px 12px #e9cba733;
            margin-bottom: 0;
            transition: background .2s, color .2s, box-shadow .2s;
            backdrop-filter: blur(6px);
            border: none;
        }
        .hero-socials.premium a:hover {
            background: #e9cba7;
            color: #222;
            box-shadow: 0 4px 18px #e9cba755;
        }
        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header h2 {
            font-size: 2.8rem;
            font-family: 'Montserrat', sans-serif;
            color: #222;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .section-header p {
            color: #555;
            font-size: 1.18rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            margin-bottom: 0;
        }

        .search-container {
            max-width: 500px;
            margin: 0 auto 40px;
        }

        #searchInput {
            width: 100%;
            padding: 12px 20px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .destination-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .destination-item {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .destination-item:last-child {
            border-bottom: none;
        }

        .destination-item h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .destination-item p {
            color: var(--secondary-color);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .explore-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent-color);
            font-weight: 500;
            text-decoration: none;
        }

        .explore-link i {
            margin-left: 5px;
        }

        .view-all {
            text-align: center;
            margin-top: 40px;
        }

        .view-all a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .section-header h2 {
                font-size: 1.8rem;
            }
            
            .destination-item h3 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 900px) {
            .hero { height: 60vh; min-height: 340px; }
            .hero-content.premium { padding: 28px 8vw 32px 8vw; }
            .hero-content.premium h1 { font-size: 2.2rem; }
            .hero-content.premium .hero-sub { font-size: 1.1rem; }
            .hero-socials.premium { left: 1vw; bottom: 4vh; }
        }
        @media (max-width: 600px) {
            .hero { height: 44vh; min-height: 180px; }
            .hero-content.premium { bottom: 24px; max-width: 98vw; gap: 18px; }
            .hero-content.premium h1 { font-size: 1.3rem; }
            .hero-content.premium .hero-sub { font-size: 0.98rem; }
            .hero-search { max-width: 98vw; }
            .hero-socials.premium { display: none; }
            .header-container { flex-direction: column; gap: 18px; height: auto; }
        }

        .villes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 38px;
            margin-top: 24px;
        }
        .ville-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 2px 16px #e9cba733;
            border: 1.5px solid #f3e9d1;
            overflow: hidden;
            transition: transform .25s, box-shadow .25s, border-color .25s;
            min-height: 340px;
            position: relative;
            will-change: transform, box-shadow, border-color;
        }
        .ville-card:hover {
            transform: translateY(-10px) scale(1.035);
            box-shadow: 0 12px 48px #e9cba799, 0 1.5px 0 #fff;
            border-color: #e9cba7;
        }
        .ville-image {
            border-radius: 28px 28px 0 0;
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 260px;
        }
        .ville-image img {
            border-radius: 28px 28px 0 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .35s cubic-bezier(.4,2,.6,1), filter .2s;
            filter: brightness(0.97) contrast(1.08);
        }
        .ville-card:hover .ville-image img {
            transform: scale(1.06);
            filter: brightness(1.03) contrast(1.13) saturate(1.1);
        }
        .badge-ville {
            top: 18px;
            right: 18px;
            background: #e9cba7;
            color: #222;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 16px;
            padding: 8px 22px;
            position: absolute;
            box-shadow: 0 2px 8px #e9cba733;
            letter-spacing: 0.5px;
            z-index: 2;
            border: none;
        }
        .badge-nom {
            left: 18px;
            bottom: 18px;
            background: #fff;
            color: #222;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            border-radius: 16px;
            padding: 10px 28px;
            position: absolute;
            box-shadow: 0 4px 18px #e9cba755;
            z-index: 2;
            border: none;
            letter-spacing: 0.5px;
        }
        @media (max-width: 900px) {
            .villes-grid { grid-template-columns: 1fr; }
            .ville-image { height: 180px; }
        }

        .ville-card.premium {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 2px 16px #e9cba733;
            border: 2px solid #e9cba7;
            overflow: hidden;
            transition: transform .3s cubic-bezier(.4,2,.6,1), box-shadow .3s, border-color .3s;
            min-height: 340px;
            position: relative;
            will-change: transform, box-shadow, border-color;
        }
        .ville-card.premium:hover {
            transform: translateY(-14px) scale(1.045);
            box-shadow: 0 16px 48px #e9cba799, 0 0 0 4px #e9cba7cc;
            border-color: #bfa14a;
        }

        .ville-image {
            border-radius: 28px 28px 0 0;
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 260px;
        }
        .ville-image img {
            border-radius: 28px 28px 0 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s cubic-bezier(.4,2,.6,1), filter .2s;
            filter: brightness(0.97) contrast(1.08);
        }
        .ville-card.premium:hover .ville-image img {
            transform: scale(1.08);
            filter: brightness(1.03) contrast(1.13) saturate(1.1);
        }
        .image-gradient {
            position: absolute;
            left: 0; right: 0; bottom: 0; height: 60px;
            background: linear-gradient(0deg, #fff 0%, #fff0 100%);
            z-index: 1;
            pointer-events: none;
            border-radius: 0 0 28px 28px;
        }

        .badge-ville.premium {
            top: 18px;
            right: 18px;
            background: rgba(233,203,167,0.95);
            color: #222;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 16px;
            padding: 7px 20px;
            position: absolute;
            box-shadow: 0 2px 8px #e9cba733;
            letter-spacing: 1.5px;
            z-index: 2;
            border: 1.5px solid #e9cba7;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }

        .badge-nom.premium {
            left: 18px;
            bottom: 18px;
            background: #fff;
            color: #222;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.25rem;
            font-weight: 900;
            border-radius: 18px;
            padding: 13px 32px;
            position: absolute;
            box-shadow: 0 6px 24px #e9cba755;
            z-index: 2;
            border: 1.5px solid #e9cba7;
            letter-spacing: 0.5px;
        }

        @media (max-width: 900px) {
            .villes-grid { grid-template-columns: 1fr; }
            .ville-image { height: 180px; }
        }

        .ville-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 18px 24px 18px 0;
            margin-top: 0;
        }
        .btn-discover {
            display: inline-block;
            background: #e9cba7;
            color: #222;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.08rem;
            border-radius: 24px;
            padding: 10px 32px;
            text-decoration: none;
            box-shadow: 0 2px 8px #e9cba733;
            border: none;
            transition: background .2s, color .2s, box-shadow .2s, transform .2s;
            letter-spacing: 0.5px;
        }
        .btn-discover:hover {
            background: #bfa14a;
            color: #fff;
            box-shadow: 0 6px 24px #e9cba799;
            transform: translateY(-2px) scale(1.04);
        }

        #city-search-result {
            position: relative;
            z-index: 20;
        }
        /* Style de la section */
.section {
    padding: 80px 0;
}

/* Style du conteneur */
.container {
    width: 90%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Style du titre de section */
.section-title {
    text-align: center;
    margin-bottom: 50px;
}

.section-title h2 {
    font-size: 2.2rem;
    margin-bottom: 15px;
    font-family: 'Playfair Display', serif;
    color: var(--primary-color);
}

.section-title p {
    color: var(--secondary-color);
    max-width: 700px;
    margin: 0 auto;
}

/* Style de la grille des fonctionnalités */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

/* Style des cartes de fonctionnalités */
.feature-card {
    text-align: center;
    padding: 30px 20px;
    background: var(--white);
    border-radius: 8px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
}

.feature-icon {
    font-size: 2.5rem;
    color: var(--accent-color);
    margin-bottom: 20px;
}

.feature-card h3 {
    margin-bottom: 15px;
    font-size: 1.2rem;
    color: var(--primary-color);
}

.feature-card p {
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.why-vmaroc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 38px;
  margin-top: 0;
}
.why-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 24px #e9cba733;
  border: 1.5px solid #e9cba7;
  padding: 38px 24px 32px 24px;
  text-align: center;
  transition: transform .25s, box-shadow .25s, border-color .25s, background .25s;
  position: relative;
  opacity: 0;
  transform: translateY(40px);
  animation: whyFadeIn 0.8s forwards;
}
.why-card:nth-child(1) { animation-delay: 0.1s; }
.why-card:nth-child(2) { animation-delay: 0.3s; }
.why-card:nth-child(3) { animation-delay: 0.5s; }
.why-card:hover {
  transform: translateY(-10px) scale(1.035);
  box-shadow: 0 12px 48px #e9cba799, 0 1.5px 0 #fff;
  border-color: #bfa14a;
  background: #f9f6f2;
}
.why-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 68px;
  height: 68px;
  border-radius: 50%;
  font-size: 2.5rem;
  margin: 0 auto 22px auto;
  box-shadow: 0 2px 8px #e9cba733;
  transition: background .2s, color .2s;
}
.why-card:hover .why-icon {
  background: #8B7355;
  color: #fff;
}
@keyframes whyFadeIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.why-card h3 {
  font-size: 1.22rem;
  color: #2D2926;
  font-family: 'Montserrat', sans-serif;
  font-weight: 800;
  margin-bottom: 12px;
}
.why-card p {
  color: #6d5c3d;
  font-size: 1.01rem;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  margin-bottom: 0;
}
@media (max-width: 900px) {
  .why-vmaroc-grid { grid-template-columns: 1fr; }
}
.why-vmaroc-section {
  background: none !important;
  position: relative;
}
.why-bg-overlay {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background: url('images/Photo.png') center center/cover no-repeat;
  z-index: 1;
}
.why-bg-overlay:after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0);
  z-index: 2;
  pointer-events: none;
}
.why-vmaroc-section .container {
  position: relative;
  z-index: 3;
}
.light-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 24px #e9cba733;
  border: 1.5px solid #e9cba7;
  padding: 38px 24px 32px 24px;
  text-align: center;
  transition: transform .25s, box-shadow .25s, border-color .25s, background .25s;
  position: relative;
  opacity: 0;
  transform: translateY(40px);
  animation: whyFadeIn 0.8s forwards;
}
.why-card:nth-child(1) { animation-delay: 0.1s; }
.why-card:nth-child(2) { animation-delay: 0.3s; }
.why-card:nth-child(3) { animation-delay: 0.5s; }
.light-card:hover {
  transform: translateY(-10px) scale(1.035);
  box-shadow: 0 12px 48px #e9cba799, 0 1.5px 0 #fff;
  border-color: #bfa14a;
  background: #f9f6f2;
}
.light-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: auto;
  height: auto;
  border-radius: 0;
  font-size: 1.8rem;
  margin: 0 auto 18px auto;
  box-shadow: none;
  background: none;
  color: #bfa14a;
  border: none;
  transition: color .2s;
}
.light-card:hover .light-icon {
  color: #8B7355;
  background: none;
  border: none;
}
.why-card h3 {
  font-size: 1.22rem;
  color: #2D2926;
  font-family: 'Montserrat', sans-serif;
  font-weight: 800;
  margin-bottom: 12px;
}
.why-card p {
  color: #8B7355;
  font-size: 1.01rem;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  margin-bottom: 0;
}
@keyframes whyFadeIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@media (max-width: 900px) {
  .why-vmaroc-grid { grid-template-columns: 1fr; }
}
.why-vmaroc-section .section-title h2,
.why-vmaroc-section .section-title p,
.why-vmaroc-section .why-card h3,
.why-vmaroc-section .why-card p {
  color:rgb(21, 20, 19) !important;
}
    </style>
</head>
<body>

<!-- Hero Fullscreen avec header intégré -->
<section class="hero" style="height:100vh; min-height:520px; display:flex; align-items:center; justify-content:center; position:relative;">
  <div class="hero-overlay"></div>
  <!-- Header intégré -->
  <?php include 'includes/header.php'; ?>
  <!-- Socials supprimés -->

  <!-- Bloc premium -->
  <div class="hero-content premium">
    <a href="#destinations" class="btn-cta">Voir les destinations Populaires</a>
        </div>
    </section>

<!-- Destinations Populaires (remplace la section existante) -->
<section class="section" id="destinations" style="background:#fff; padding: 60px 0 40px 0;">
        <div class="container">
    <h2 style="text-align:center; font-family:'Playfair Display',serif; font-size:2.6rem; font-weight:800; margin-bottom:18px;">Destinations Populaires</h2>
    <p style="text-align:center; max-width:700px; margin:0 auto 32px auto; color:#444; font-size:1.13rem;">Explorez les villes les plus emblématiques du Maroc et découvrez leur richesse culturelle, historique et gastronomique.</p>
    <div class="search-container" style="display:flex; justify-content:center; margin-bottom:32px; position:relative;">
      <input type="text" id="searchInput" placeholder="Rechercher une ville..." style="width:320px; height:44px; border-radius:22px; border:2px solid #e9cba7; font-size:1.08rem; padding:0 18px; font-family:Montserrat,sans-serif; color:#222; background:#fff; box-shadow:0 2px 8px #e9cba733;">
      <span class="search-icon" style="margin-left:-36px; color:#bfa14a; font-size:1.2rem; align-self:center; cursor:pointer;">&#128269;</span>
      <div class="search-suggestions" id="searchSuggestions" style="display:none; position:absolute; left:0; right:0; top:48px; background:#fff; border-radius:0 0 18px 18px; box-shadow:0 4px 18px #e9cba755; z-index:10; list-style:none; margin:0 auto; padding:0; max-height:220px; overflow-y:auto; width:320px;"></div>
            </div>
    <div class="destination-grid" id="destinationGrid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:32px;">
      <?php foreach ($cities as $city): ?>
        <?php if (!in_array(strtolower($city['nom']), $popular)) continue; ?>
        <div class="destination-card" style="background:#fff; border-radius:16px; box-shadow:0 4px 18px #e9cba755; border:1.5px solid #e9cba7; overflow:hidden; display:flex; flex-direction:column;">
          <div class="card-img" style="height:220px; background:#f3e9d1; overflow:hidden;">
            <img src="<?= htmlspecialchars(getCityImageUrl($city['nom'], $city['photo'])) ?>"
                alt="<?= htmlspecialchars($city['nom']) ?>"
                style="width:100%; height:100%; object-fit:cover;">
          </div>
          <div class="card-body" style="padding:24px 22px 18px 22px; flex:1; display:flex; flex-direction:column;">
            <h3 style="font-family:Montserrat,sans-serif; font-size:1.18rem; font-weight:800; color:#222; margin-bottom:10px;">
              <?= htmlspecialchars($city['nom']) ?>
            </h3>
            <p style="color:#555; font-size:0.99rem; margin-bottom:18px; flex:1; line-height:1.6;">
              <?= !empty($city['description']) ? htmlspecialchars($city['description']) : 'Découvrez la beauté et l\'authenticité de cette ville marocaine.' ?>
            </p>
            <a href="city.php?id=<?= $city['id'] ?>" class="explore-btn" style="color:#bfa14a; font-weight:600; text-decoration:none; font-family:Montserrat,sans-serif; font-size:1.05rem;">Explorer <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      <?php endforeach; ?>
            </div>
    <div style="text-align:center; margin-top:38px;">
      <a href="destinations.php" class="btn-outline" style="padding:12px 30px; border:1.5px solid #e9cba7; color:#bfa14a; font-weight:600; border-radius:8px; font-size:1.08rem; background:#fff; text-decoration:none;">Voir toutes les destinations <i class="far fa-smile"></i></a>
            </div>
            </div>
        </section>


 <!-- Why Choose Section -->
 <section class="section why-vmaroc-section" style="position:relative; padding: 90px 0 70px 0; overflow:hidden;">
  <div class="why-bg-overlay"></div>
  <div class="container" style="position:relative; z-index:2;">
    <div class="section-title" style="margin-bottom: 60px;">
      <h2 style="font-size:2.7rem; font-family:'Playfair Display',serif; font-weight:900; color:#2D2926; margin-bottom:12px; letter-spacing:-1px;">Pourquoi Choisir VMaroc&nbsp;?</h2>
      <p style="color:#8B7355; font-size:1.18rem; font-family:'Montserrat',sans-serif; font-weight:500; max-width:700px; margin:0 auto;">VMaroc vous aide à créer l'expérience de voyage parfaite au Maroc avec des outils adaptés à vos envies et vos besoins.</p>
    </div>
    <div class="features-grid why-vmaroc-grid">
      <div class="feature-card why-card light-card">
        <div class="feature-icon why-icon light-icon">
          <i class="fas fa-search-location"></i>
        </div>
        <h3>Explorez en Détail</h3>
        <p>Découvrez chaque ville avec des infos sur les attractions, hôtels, restaurants et plus encore.</p>
      </div>
      <div class="feature-card why-card light-card">
        <div class="feature-icon why-icon light-icon">
          <i class="fas fa-heart"></i>
        </div>
        <h3>Recommandations Personnalisées</h3>
        <p>Obtenez des suggestions adaptées à vos intérêts, votre budget et la durée de votre séjour.</p>
      </div>
      <div class="feature-card why-card light-card">
        <div class="feature-icon why-icon light-icon">
          <i class="fas fa-desktop"></i>
        </div>
        <h3>Interface Intuitive</h3>
        <p>Naviguez facilement dans notre application avec une interface claire et des filtres pratiques.</p>
      </div>
    </div>
  </div>
</section>


<!-- Footer -->
<footer>
        <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:60px;">
                <p style="margin:18px 0 0 0; color:#555;">Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                <!-- Social links supprimés -->
            </div>
            <div class="footer-col">
                <h3>Liens Rapides</h3>
                    <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="destinations.php">Destinations</a></li>
                    <li><a href="recommendations.php">Recommandations</a></li>
                    </ul>
                </div>
            <div class="footer-col">
                <h3>Villes Populaires</h3>
                    <ul>
                    <?php
                    foreach ($popular as $pop) {
                        foreach ($cities as $city) {
                            if (strtolower($city['nom']) === $pop) {
                                echo '<li><a href="city.php?id=' . $city['id'] . '">' . htmlspecialchars($city['nom']) . '</a></li>';
                                break;
                            }
                        }
                    }
                    ?>
                    </ul>
                </div>
            <div class="footer-col">
                <h3>Contact</h3>
                <p>contact@marocauthentique.com</p>
                <p>+212 522 123 456</p>
            </div>
        </div>
            <div class="copyright">
                <p style="font-family: 'Montserrat', sans-serif;">© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p style="font-family: 'Montserrat', sans-serif; margin-top: 10px;">
                    <a href="politique-confidentialite.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Politique de confidentialité</a> | 
                    <a href="conditions-utilisation.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>

<script>
const allCities = <?php echo json_encode($cities); ?>;
const searchInput = document.getElementById('searchInput');
const grid = document.getElementById('destinationGrid');
const suggestionsBox = document.getElementById('searchSuggestions');
const popular = <?php echo json_encode($popular); ?>;

searchInput.addEventListener('input', function() {
  const value = this.value.trim().toLowerCase();
  suggestionsBox.innerHTML = '';
  
  if (value.length === 0) {
    suggestionsBox.style.display = 'none';
    // Afficher uniquement les villes populaires
    Array.from(grid.children).forEach(card => {
      const cardName = card.querySelector('h3').textContent.trim().toLowerCase();
      card.style.display = popular.includes(cardName) ? '' : 'none';
    });
    return;
  }

  const matches = allCities.filter(city => 
    city.nom.toLowerCase().includes(value)
  );

  if (matches.length === 0) {
    suggestionsBox.style.display = 'none';
    Array.from(grid.children).forEach(card => card.style.display = 'none');
    return;
  }

  matches.forEach(city => {
    const div = document.createElement('div');
    div.textContent = city.nom;
    div.style.padding = '12px 18px';
    div.style.cursor = 'pointer';
    div.style.color = '#222';
    div.style.fontFamily = 'Montserrat, sans-serif';
    div.style.fontWeight = '600';
    div.style.fontSize = '1.08rem';
    div.style.borderBottom = '1px solid #f3e9d1';
    
    div.addEventListener('mousedown', function(e) {
      e.preventDefault();
      searchInput.value = city.nom;
      suggestionsBox.style.display = 'none';
      // Afficher uniquement la carte de la ville sélectionnée
      Array.from(grid.children).forEach(card => {
        const cardName = card.querySelector('h3').textContent.trim().toLowerCase();
        card.style.display = cardName === city.nom.toLowerCase() ? '' : 'none';
      });
    });

    div.addEventListener('mouseover', function() {
      div.style.background = '#f3e9d1';
    });

    div.addEventListener('mouseout', function() {
      div.style.background = '#fff';
    });

    suggestionsBox.appendChild(div);
  });

  suggestionsBox.style.display = 'block';
});

// Fermer les suggestions en cliquant ailleurs
document.addEventListener('click', function(e) {
  if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
    suggestionsBox.style.display = 'none';
  }
});

// Navigation au clavier dans les suggestions
searchInput.addEventListener('keydown', function(e) {
  const suggestions = suggestionsBox.querySelectorAll('div');
  const currentFocus = Array.from(suggestions).findIndex(el => el.style.background === '#f3e9d1');
  
  if (suggestions.length === 0) return;
  
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    const nextFocus = currentFocus < suggestions.length - 1 ? currentFocus + 1 : 0;
    suggestions.forEach((s, i) => s.style.background = i === nextFocus ? '#f3e9d1' : '#fff');
    if (nextFocus >= 0) {
      searchInput.value = suggestions[nextFocus].textContent;
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    const nextFocus = currentFocus > 0 ? currentFocus - 1 : suggestions.length - 1;
    suggestions.forEach((s, i) => s.style.background = i === nextFocus ? '#f3e9d1' : '#fff');
    if (nextFocus >= 0) {
      searchInput.value = suggestions[nextFocus].textContent;
    }
  } else if (e.key === 'Enter' && currentFocus >= 0) {
    e.preventDefault();
    suggestions[currentFocus].click();
  }
});

let sliderIndex = 0;
const sliderImgs = document.querySelectorAll('.slider-img');
setInterval(() => {
    sliderImgs.forEach((img, i) => {
        img.style.opacity = (i === sliderIndex) ? '1' : '0';
        img.classList.toggle('active', i === sliderIndex);
    });
    sliderIndex = (sliderIndex + 1) % sliderImgs.length;
}, 3500); // Change d'image toutes les 3,5 secondes

(function() {
    const slider = document.getElementById('heroSlider');
    const slides = slider.querySelectorAll('.hero-slide');
    const prevBtn = document.getElementById('heroPrev');
    const nextBtn = document.getElementById('heroNext');
    let current = 0;
    let autoSlide;

    function goToSlide(idx) {
        current = (idx + slides.length) % slides.length;
        slider.style.transform = `translateX(-${current * 100}%)`;
    }

    function nextSlide() {
        goToSlide(current + 1);
    }
    function prevSlide() {
        goToSlide(current - 1);
    }

    nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAuto();
    });
    prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAuto();
    });

    function resetAuto() {
        clearInterval(autoSlide);
        autoSlide = setInterval(nextSlide, 3500);
    }

    autoSlide = setInterval(nextSlide, 3500);
})();

document.querySelector('.btn-cta').addEventListener('click', function(e) {
    e.preventDefault();
    const section = document.querySelector('.section');
    if(section) section.scrollIntoView({ behavior: 'smooth' });
});
</script>
</body>
</html>
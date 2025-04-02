

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if ($_SESSION['userLoggedIn'] == false) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get the ID as a number
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $result ? (int) $result['id'] : null;

// Fetch user profile information
$stmt = $pdo->prepare("SELECT *, updated FROM profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);


$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        // Sanitize and validate inputs
        $firstname = sanitize_input($_POST['firstname']);
        $surname = sanitize_input($_POST['surname']);
        $other_names = sanitize_input($_POST['other_names']);
        $date_of_birth = $_POST['date_of_birth'];
        $occupation = sanitize_input($_POST['occupation']);
        $highest_qualification = $_POST['highest_qualification'];
        $gender = $_POST['gender'];
        $state = $_POST['state'];
        $lga = $_POST['lga'];
        $street_address = sanitize_input($_POST['street_address']);
        $profile_picture = $_FILES['profile_picture'];

        // Validate required fields
        if (empty($firstname) || empty($surname)) {
            $error = "First name and surname are required.";
        } elseif (!validate_date($date_of_birth)) {
            $error = "Invalid date of birth.";
        } else {
            // Handle profile picture upload securely
            $profile_picture_path = $profile['profile_picture'] ?? null;
            if (isset($profile_picture) && $profile_picture['error'] == UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (in_array($profile_picture['type'], $allowed_types) && $profile_picture['size'] <= $max_size) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $file_ext = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid('profile_', true) . '.' . $file_ext;
                    $upload_file = $upload_dir . $new_filename;
                    if (move_uploaded_file($profile_picture['tmp_name'], $upload_file)) {
                        $profile_picture_path = $upload_file;
                    } else {
                        $error = "Failed to upload profile picture.";
                    }
                } else {
                    $error = "Invalid file type or size for profile picture.";
                }
            }

            if (empty($error)) {
                try {
                    if ($profile) {
                        // Update existing profile
                        $stmt = $pdo->prepare("UPDATE profiles SET firstname = ?, surname = ?, other_names = ?, 
                            date_of_birth = ?, occupation = ?, highest_qualification = ?, gender = ?, 
                            state = ?, lga = ?, street_address = ?, profile_picture = ?, updated = 1 
                            WHERE user_id = ?");
                        $params = [
                            $firstname,
                            $surname,
                            $other_names,
                            $date_of_birth,
                            $occupation,
                            $highest_qualification,
                            $gender,
                            $state,
                            $lga,
                            $street_address,
                            $profile_picture_path,
                            $user_id
                        ];
                    } else {
                        // Generate random membership ID: JCDA- + random letter (A-Z) + random 4 digits
                        $random_letter = chr(rand(65, 90)); // A-Z in ASCII
                        $random_digits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                        $membership_id = 'JCDA-' . $random_letter . $random_digits;

                        $stmt = $pdo->prepare("INSERT INTO profiles (firstname, surname, other_names, 
                                            date_of_birth, occupation, highest_qualification, gender, state, lga, 
                                            street_address, profile_picture, user_id, membership_id_no, updated) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

                        $params = [
                            $firstname,
                            $surname,
                            $other_names,
                            $date_of_birth,
                            $occupation,
                            $highest_qualification,
                            $gender,
                            $state,
                            $lga,
                            $street_address,
                            $profile_picture_path,
                            $user_id,
                            $membership_id
                        ];
                    }

                    if ($stmt->execute($params)) {
                        $success = "Profile information saved successfully.";
                        // Refresh profile data
                        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = "Failed to update profile. Please try again.";
                    }
                } catch (PDOException $e) {
                    $error = "An error occurred. Please try again later.";
                    log_error($e->getMessage());
                }
            }
        }
    }
}

// Define states and LGAs array
$states_lgas = [
    'Abia' => ['Aba North', 'Aba South', 'Arochukwu', 'Bende', 'Ikwuano', 'Isiala Ngwa North', 'Isiala Ngwa South', 'Isuikwuato', 'Obi Ngwa', 'Ohafia', 'Osisioma', 'Ugwunagbo', 'Ukwa East', 'Ukwa West', 'Umuahia North', 'Umuahia South', 'Umu Nneochi'],
    'Adamawa' => ['Demsa', 'Fufore', 'Ganye', 'Girei', 'Gombi', 'Guyuk', 'Hong', 'Jada', 'Lamurde', 'Madagali', 'Maiha', 'Mayo Belwa', 'Michika', 'Mubi North', 'Mubi South', 'Numan', 'Shelleng', 'Song', 'Toungo', 'Yola North', 'Yola South'],
    'Akwa Ibom' => ['Abak', 'Eastern Obolo', 'Eket', 'Esit Eket', 'Essien Udim', 'Etim Ekpo', 'Etinan', 'Ibeno', 'Ibesikpo Asutan', 'Ibiono Ibom', 'Ika', 'Ikono', 'Ikot Abasi', 'Ikot Ekpene', 'Ini', 'Itu', 'Mbo', 'Mkpat Enin', 'Nsit Atai', 'Nsit Ibom', 'Nsit Ubium', 'Obot Akara', 'Okobo', 'Onna', 'Oron', 'Oruk Anam', 'Udung Uko', 'Ukanafun', 'Uruan', 'Urue Offong Oruko', 'Uyo'],
    'Anambra' => ['Aguata', 'Anambra East', 'Anambra West', 'Anaocha', 'Awka North', 'Awka South', 'Ayamelum', 'Dunukofia', 'Ekwusigo', 'Idemili North', 'Idemili South', 'Ihiala', 'Njikoka', 'Nnewi North', 'Nnewi South', 'Ogbaru', 'Onitsha North', 'Onitsha South', 'Orumba North', 'Orumba South', 'Oyi'],
    'Bauchi' => ['Alkaleri', 'Bauchi', 'Bogoro', 'Damban', 'Darazo', 'Dass', 'Gamawa', 'Ganjuwa', 'Giade', 'Itas/Gadau', 'Jama\'are', 'Katagum', 'Kirfi', 'Misau', 'Ningi', 'Shira', 'Tafawa Balewa', 'Toro', 'Warji', 'Zaki'],
    'Bayelsa' => ['Brass', 'Ekeremor', 'Kolokuma/Opokuma', 'Nembe', 'Ogbia', 'Sagbama', 'Southern Ijaw', 'Yenagoa'],
    'Benue' => ['Ado', 'Agatu', 'Apa', 'Buruku', 'Gboko', 'Guma', 'Gwer East', 'Gwer West', 'Katsina-Ala', 'Konshisha', 'Kwande', 'Logo', 'Makurdi', 'Obi', 'Ogbadibo', 'Ohimini', 'Oju', 'Okpokwu', 'Otukpo', 'Tarka', 'Ukum', 'Ushongo', 'Vandeikya'],
    'Borno' => ['Abadam', 'Askira/Uba', 'Bama', 'Bayo', 'Biu', 'Chibok', 'Damboa', 'Dikwa', 'Gubio', 'Guzamala', 'Gwoza', 'Hawul', 'Jere', 'Kaga', 'Kala/Balge', 'Konduga', 'Kukawa', 'Kwaya Kusar', 'Mafa', 'Magumeri', 'Maiduguri', 'Marte', 'Mobbar', 'Monguno', 'Ngala', 'Nganzai', 'Shani'],
    'Cross River' => ['Abi', 'Akamkpa', 'Akpabuyo', 'Bakassi', 'Bekwarra', 'Biase', 'Boki', 'Calabar Municipal', 'Calabar South', 'Etung', 'Ikom', 'Obanliku', 'Obubra', 'Obudu', 'Odukpani', 'Ogoja', 'Yakuur', 'Yala'],
    'Delta' => ['Aniocha North', 'Aniocha South', 'Bomadi', 'Burutu', 'Ethiope East', 'Ethiope West', 'Ika North East', 'Ika South', 'Isoko North', 'Isoko South', 'Ndokwa East', 'Ndokwa West', 'Okpe', 'Oshimili North', 'Oshimili South', 'Patani', 'Sapele', 'Udu', 'Ughelli North', 'Ughelli South', 'Ukwuani', 'Uvwie', 'Warri North', 'Warri South', 'Warri South West'],
    'Ebonyi' => ['Abakaliki', 'Afikpo North', 'Afikpo South', 'Ebonyi', 'Ezza North', 'Ezza South', 'Ikwo', 'Ishielu', 'Ivo', 'Izzi', 'Ohaozara', 'Ohaukwu', 'Onicha'],
    'Edo' => ['Akoko-Edo', 'Egor', 'Esan Central', 'Esan North-East', 'Esan South-East', 'Esan West', 'Etsako Central', 'Etsako East', 'Etsako West', 'Igueben', 'Ikpoba-Okha', 'Oredo', 'Orhionmwon', 'Ovia North-East', 'Ovia South-West', 'Owan East', 'Owan West', 'Uhunmwonde'],
    'Ekiti' => ['Ado Ekiti', 'Efon', 'Ekiti East', 'Ekiti South-West', 'Ekiti West', 'Emure', 'Gbonyin', 'Ido Osi', 'Ijero', 'Ikere', 'Ikole', 'Ilejemeje', 'Irepodun/Ifelodun', 'Ise/Orun', 'Moba', 'Oye'],
    'Enugu' => ['Aninri', 'Awgu', 'Enugu East', 'Enugu North', 'Enugu South', 'Ezeagu', 'Igbo Etiti', 'Igbo Eze North', 'Igbo Eze South', 'Isi Uzo', 'Nkanu East', 'Nkanu West', 'Nsukka', 'Oji River', 'Udenu', 'Udi', 'Uzo Uwani'],
    'Gombe' => ['Akko', 'Balanga', 'Billiri', 'Dukku', 'Funakaye', 'Gombe', 'Kaltungo', 'Kwami', 'Nafada', 'Shongom', 'Yamaltu/Deba'],
    'Imo' => ['Aboh Mbaise', 'Ahiazu Mbaise', 'Ehime Mbano', 'Ezinihitte', 'Ideato North', 'Ideato South', 'Ihitte/Uboma', 'Ikeduru', 'Isiala Mbano', 'Isu', 'Mbaitoli', 'Ngor Okpala', 'Njaba', 'Nkwerre', 'Nwangele', 'Obowo', 'Oguta', 'Ohaji/Egbema', 'Okigwe', 'Onuimo', 'Orlu', 'Orsu', 'Oru East', 'Oru West', 'Owerri Municipal', 'Owerri North', 'Owerri West'],
    'Jigawa' => ['Auyo', 'Babura', 'Biriniwa', 'Birnin Kudu', 'Buji', 'Dutse', 'Gagarawa', 'Garki', 'Gumel', 'Guri', 'Gwaram', 'Gwiwa', 'Hadejia', 'Jahun', 'Kafin Hausa', 'Kaugama', 'Kazaure', 'Kiri Kasama', 'Kiyawa', 'Maigatari', 'Malam Madori', 'Miga', 'Ringim', 'Roni', 'Sule Tankarkar', 'Taura', 'Yankwashi'],
    'Kaduna' => ['Birnin Gwari', 'Chikun', 'Giwa', 'Igabi', 'Ikara', 'Jaba', 'Jema\'a', 'Kachia', 'Kaduna North', 'Kaduna South', 'Kagarko', 'Kajuru', 'Kaura', 'Kauru', 'Kubau', 'Kudan', 'Lere', 'Makarfi', 'Sabon Gari', 'Sanga', 'Soba', 'Zangon Kataf', 'Zaria'],
    'Kano' => ['Ajingi', 'Albasu', 'Bagwai', 'Bebeji', 'Bichi', 'Bunkure', 'Dala', 'Dambatta', 'Dawakin Kudu', 'Dawakin Tofa', 'Doguwa', 'Fagge', 'Gabasawa', 'Garko', 'Garun Mallam', 'Gaya', 'Gezawa', 'Gwale', 'Gwarzo', 'Kabo', 'Kano Municipal', 'Karaye', 'Kibiya', 'Kiru', 'Kumbotso', 'Kunchi', 'Kura', 'Madobi', 'Makoda', 'Minjibir', 'Nasarawa', 'Rano', 'Rimin Gado', 'Rogo', 'Shanono', 'Sumaila', 'Takai', 'Tarauni', 'Tofa', 'Tsanyawa', 'Tudun Wada', 'Ungogo', 'Warawa', 'Wudil'],
    'Katsina' => ['Bakori', 'Batagarawa', 'Batsari', 'Baure', 'Bindawa', 'Charanchi', 'Dandume', 'Danja', 'Dan Musa', 'Daura', 'Dutsi', 'Dutsin Ma', 'Faskari', 'Funtua', 'Ingawa', 'Jibia', 'Kafur', 'Kaita', 'Kankara', 'Kankia', 'Katsina', 'Kurfi', 'Kusada', 'Mai\'Adua', 'Malumfashi', 'Mani', 'Mashi', 'Matazu', 'Musawa', 'Rimi', 'Sabuwa', 'Safana', 'Sandamu', 'Zango'],
    'Kebbi' => ['Aleiro', 'Arewa Dandi', 'Argungu', 'Augie', 'Bagudo', 'Birnin Kebbi', 'Bunza', 'Dandi', 'Fakai', 'Gwandu', 'Jega', 'Kalgo', 'Koko/Besse', 'Maiyama', 'Ngaski', 'Sakaba', 'Shanga', 'Suru', 'Wasagu/Danko', 'Yauri', 'Zuru'],
    'Kogi' => ['Adavi', 'Ajaokuta', 'Ankpa', 'Bassa', 'Dekina', 'Ibaji', 'Idah', 'Igalamela Odolu', 'Ijumu', 'Kabba/Bunu', 'Kogi', 'Lokoja', 'Mopa Muro', 'Ofu', 'Ogori/Magongo', 'Okehi', 'Okene', 'Olamaboro', 'Omala', 'Yagba East', 'Yagba West'],
    'Kwara' => ['Asa', 'Baruten', 'Edu', 'Ekiti', 'Ifelodun', 'Ilorin East', 'Ilorin South', 'Ilorin West', 'Irepodun', 'Isin', 'Kaiama', 'Moro', 'Offa', 'Oke Ero', 'Oyun', 'Pategi'],
    'Lagos' => ['Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa', 'Badagry', 'Epe', 'Eti Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye', 'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island', 'Lagos Mainland', 'Mushin', 'Ojo', 'Oshodi-Isolo', 'Shomolu', 'Surulere'],
    'Nasarawa' => ['Akwanga', 'Awe', 'Doma', 'Karu', 'Keana', 'Keffi', 'Kokona', 'Lafia', 'Nasarawa', 'Nasarawa Egon', 'Obi', 'Toto', 'Wamba'],
    'Niger' => ['Agaie', 'Agwara', 'Bida', 'Borgu', 'Bosso', 'Chanchaga', 'Edati', 'Gbako', 'Gurara', 'Katcha', 'Kontagora', 'Lapai', 'Lavun', 'Magama', 'Mariga', 'Mashegu', 'Mokwa', 'Moya', 'Paikoro', 'Rafi', 'Rijau', 'Shiroro', 'Suleja', 'Tafa', 'Wushishi'],
    'Ogun' => ['Abeokuta North', 'Abeokuta South', 'Ado-Odo/Ota', 'Egbado North', 'Egbado South', 'Ewekoro', 'Ifo', 'Ijebu East', 'Ijebu North', 'Ijebu North East', 'Ijebu Ode', 'Ikenne', 'Imeko Afon', 'Ipokia', 'Obafemi Owode', 'Odeda', 'Odogbolu', 'Ogun Waterside', 'Remo North', 'Shagamu'],
    'Ondo' => ['Akoko North-East', 'Akoko North-West', 'Akoko South-East', 'Akoko South-West', 'Akure North', 'Akure South', 'Ese Odo', 'Idanre', 'Ifedore', 'Ilaje', 'Ile Oluji/Okeigbo', 'Irele', 'Odigbo', 'Okitipupa', 'Ondo East', 'Ondo West', 'Ose', 'Owo'],
    'Osun' => ['Atakunmosa East', 'Atakunmosa West', 'Aiyedaade', 'Aiyedire', 'Boluwaduro', 'Boripe', 'Ede North', 'Ede South', 'Egbedore', 'Ejigbo', 'Ife Central', 'Ife East', 'Ife North', 'Ife South', 'Ifedayo', 'Ifelodun', 'Ila', 'Ilesa East', 'Ilesa West', 'Irepodun', 'Irewole', 'Isokan', 'Iwo', 'Obokun', 'Odo Otin', 'Ola Oluwa', 'Olorunda', 'Oriade', 'Orolu', 'Osogbo'],
    'Oyo' => ['Afijio', 'Akinyele', 'Atiba', 'Atisbo', 'Egbeda', 'Ibadan North', 'Ibadan North-East', 'Ibadan North-West', 'Ibadan South-East', 'Ibadan South-West', 'Ibarapa Central', 'Ibarapa East', 'Ibarapa North', 'Ido', 'Irepo', 'Iseyin', 'Itesiwaju', 'Iwajowa', 'Kajola', 'Lagelu', 'Ogbomosho North', 'Ogbomosho South', 'Ogo Oluwa', 'Olorunsogo', 'Oluyole', 'Ona Ara', 'Orelope', 'Ori Ire', 'Oyo East', 'Oyo West', 'Saki East', 'Saki West', 'Surulere'],
    'Plateau' => ['Bokkos', 'Barkin Ladi', 'Bassa', 'Jos East', 'Jos North', 'Jos South', 'Kanam', 'Kanke', 'Langtang North', 'Langtang South', 'Mangu', 'Mikang', 'Pankshin', 'Qua\'an Pan', 'Riyom', 'Shendam', 'Wase'],
    'Rivers' => ['Abua/Odual', 'Ahoada East', 'Ahoada West', 'Akuku-Toru', 'Andoni', 'Asari-Toru', 'Bonny', 'Degema', 'Eleme', 'Emohua', 'Etche', 'Gokana', 'Ikwerre', 'Khana', 'Obio/Akpor', 'Ogba/Egbema/Ndoni', 'Ogu/Bolo', 'Okrika', 'Omuma', 'Opobo/Nkoro', 'Oyigbo', 'Port Harcourt', 'Tai'],
    'Sokoto' => ['Binji', 'Bodinga', 'Dange Shuni', 'Gada', 'Goronyo', 'Gudu', 'Gwadabawa', 'Illela', 'Isa', 'Kebbe', 'Kware', 'Rabah', 'Sabon Birni', 'Shagari', 'Silame', 'Sokoto North', 'Sokoto South', 'Tambuwal', 'Tangaza', 'Tureta', 'Wamako', 'Wurno', 'Yabo'],
    'Taraba' => ['Ardo Kola', 'Bali', 'Donga', 'Gashaka', 'Gassol', 'Ibi', 'Jalingo', 'Karim Lamido', 'Kurmi', 'Lau', 'Sardauna', 'Takum', 'Ussa', 'Wukari', 'Yorro', 'Zing'],
    'Yobe' => ['Bade', 'Bursari', 'Damaturu', 'Fika', 'Fune', 'Geidam', 'Gujba', 'Gulani', 'Jakusko', 'Karasuwa', 'Machina', 'Nangere', 'Nguru', 'Potiskum', 'Tarmuwa', 'Yunusari', 'Yusufari'],
    'Zamfara' => ['Anka', 'Bakura', 'Birnin Magaji/Kiyaw', 'Bukkuyum', 'Bungudu', 'Gummi', 'Gusau', 'Kaura Namoda', 'Maradun', 'Maru', 'Shinkafi', 'Talata Mafara', 'Tsafe', 'Zurmi']
    // Add other states and their LGAs...
];

$profile_picture = $profile['profile_picture'] ?? 'assets/images/useravatar.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <title>Profile</title>
    <?php include 'layouts/title-meta.php'; ?>

    <!-- Daterangepicker css -->
    <link rel="stylesheet" href="assets/vendor/daterangepicker/daterangepicker.css">

    <!-- Vector Map css -->
    <link rel="stylesheet" href="assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <?php include 'layouts/head-css.php'; ?>
    <style>
        .modal-body p {
        display: flex;
        justify-content: space-between;
        align-content: center;
        align-items: center;
        margin-bottom: 5px;
        border-bottom: 1px solid #dbdada;
        padding-bottom: 6px;
    }

    .modal-body p strong {
        margin: 0 !important;
    }
    </style>
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'layouts/menu.php'; ?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <form class="d-flex">
                                        <a href="" class="btn btn-primary ms-2">
                                            <i class="ri-refresh-line"></i>
                                        </a>
                                    </form>
                                </div>
                                <h4 class="page-title">Profile Information</h4>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success" role="alert" id="successAlert" style="display: none;">
                        <strong>Success - </strong> Profile saved successfully.
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div
                                    class="card-body <?php echo ($profile && $profile['updated']) ? 'form-submitted' : ''; ?>">
                                    <form class="row" id="profileForm" action="profile.php" method="POST"
                                        enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Surname</label>
                                                <input type="text" id="surname" name="surname" class="form-control"
                                                    value="<?php echo $profile ? htmlspecialchars($profile['surname']) : ''; ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">First Name</label>
                                                <input type="text" id="firstname" name="firstname" class="form-control"
                                                    value="<?php echo $profile ? htmlspecialchars($profile['firstname']) : ''; ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Other Names</label>
                                                <input type="text" id="other_names" name="other_names"
                                                    class="form-control"
                                                    value="<?php echo $profile ? htmlspecialchars($profile['other_names']) : ''; ?>">
                                            </div>
                                        </div>


                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="example-date" class="form-label">Date of Birth</label>
                                                <input style="padding-top: 8px;" type="date" id="date_of_birth"
                                                    name="date_of_birth" class="form-control"
                                                    value="<?php echo $profile ? $profile['date_of_birth'] : ''; ?>"
                                                    required>

                                                <div role="alert" id="ageError" style="margin-top: 10px;color: #d94e6a;display: none">
                                                    <strong>Error - </strong> Age must be between 18 and 100 years
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">Gender</label>
                                                <select id="gender" name="gender" class="form-select" required
                                                    style="padding-top: 8px;">
                                                    <option value="">Select Gender</option>
                                                    <option value="male" <?php echo ($profile && $profile['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="female" <?php echo ($profile && $profile['gender'] == 'female') ? 'selected' : ''; ?>>Female
                                                    </option>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Occupation</label>
                                                <input type="text" id="occupation" name="occupation"
                                                    class="form-control"
                                                    value="<?php echo $profile ? htmlspecialchars($profile['occupation']) : ''; ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">Highest Academic
                                                    Qualification:</label>
                                                <select id="highest_qualification" name="highest_qualification"
                                                    class="form-select" required style="padding-top: 8px;">
                                                    <option value="">Select Qualification</option>
                                                    <option value="SSCE" <?php echo ($profile && $profile['highest_qualification'] == 'SSCE') ? 'selected' : ''; ?>>SSCE</option>
                                                    <option value="ND" <?php echo ($profile && $profile['highest_qualification'] == 'ND') ? 'selected' : ''; ?>>
                                                        ND</option>
                                                    <option value="HND" <?php echo ($profile && $profile['highest_qualification'] == 'HND') ? 'selected' : ''; ?>>
                                                        HND</option>
                                                    <option value="BSc" <?php echo ($profile && $profile['highest_qualification'] == 'BSc') ? 'selected' : ''; ?>>
                                                        BSc</option>
                                                    <option value="MSc" <?php echo ($profile && $profile['highest_qualification'] == 'MSc') ? 'selected' : ''; ?>>
                                                        MSc</option>
                                                    <option value="PhD" <?php echo ($profile && $profile['highest_qualification'] == 'PhD') ? 'selected' : ''; ?>>
                                                        PhD</option>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">State</label>
                                                <?php if ($profile && $profile['updated']): ?>
                                                    <input type="text" class="form-control" 
                                                    value="<?php echo htmlspecialchars($profile['state']); ?>" disabled>
                                                <?php else: ?>
                                                    <select id="state" name="state" class="form-select" required style="padding-top: 8px;">
                                                        <option value="">Select State</option>
                                                        <?php foreach ($states_lgas as $state => $lgas): ?>
                                                            <option value="<?php echo htmlspecialchars($state); ?>" 
                                                                <?php echo ($profile && $profile['state'] == $state) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($state); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="example-select" class="form-label">LGA</label>
                                                <?php if ($profile && $profile['updated']): ?>
                                                    <input type="text" class="form-control" 
                                                    value="<?php echo htmlspecialchars($profile['lga']); ?>" disabled>
                                                <?php else: ?>
                                                    <select id="lga" name="lga" class="form-select" required placeholder="" style="padding-top: 8px;">
                                                    <option value="">Select LGA</option>
                                                    <?php
                                                    if ($profile && $profile['state']) {
                                                        foreach ($states_lgas[$profile['state']] as $lga) {
                                                            echo '<option value="' . htmlspecialchars($lga) . '" ' . 
                                                                ($profile['lga'] == $lga ? 'selected' : '') . '>' . 
                                                                htmlspecialchars($lga) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label for="simpleinput" class="form-label">Street Address</label>
                                                <input type="text" id="street_address" name="street_address" class="form-control" 
                                        value="<?php echo $profile ? htmlspecialchars($profile['street_address']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="example-fileinput" class="form-label">Profile Picture</label>
                                            <?php if ($profile && $profile['updated']): ?>
                                                <div style="margin-top: 10px;margin-bottom: 30px;">
                                                <img  src="<?php echo $profile_picture; ?>" alt="" style="max-width: 200px; max-height: 200px;">
                                            </div>
                                            <?php else: ?>
                                                <input type="file" id="profile_picture" name="profile_picture" class="form-control">
                                            <?php endif; ?>
                                        </div>

                                        <div id="imagePreviewContainer" style="margin-top: 10px;margin-bottom: 30px;">
                                            <img id="imagePreview" src="#" alt="" style="max-width: 200px; max-height: 200px;">
                                        </div>

                                       

                                        <button class="btn btn-primary" type="button" id="submitBtn" style="width: 150px;"> Save Profile </button>
                                    </form>
                                    <!-- end row-->
                                </div> <!-- end card-body -->


                                <!-- Confirmation Modal -->
                                <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="confirmationModalLabel">Confirm Profile Information</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <h5 class="modal-body" style="font-weight: 200 !important;font-size: 15px;color: #686767;margin-bottom: 0px;">Please review your details carefully before submitting. Ensure all information is accurate. Edits cannot be made before final submission.</h5>
                                            <div class="modal-body" id="confirmationContent">
                                                <!-- Dynamic content will be inserted here -->
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="button" id="confirmSubmit" class="btn btn-primary">Yes, Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- container -->

        </div>
        <!-- content -->

        <?php include 'layouts/footer.php'; ?>

    </div>

    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <?php include 'layouts/right-sidebar.php'; ?>

    <?php include 'layouts/footer-scripts.php'; ?>

    <!-- Daterangepicker js -->
    <script src="assets/vendor/daterangepicker/moment.min.js"></script>
    <script src="assets/vendor/daterangepicker/daterangepicker.js"></script>

    <!-- Apex Charts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>

    <!-- Vector Map js -->
    <script src="assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

    <!-- Dashboard App js -->
    <script src="assets/js/pages/demo.dashboard.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Add this to your $(document).ready() function
$('#profile_picture').change(function() {
    const file = this.files[0];
    const previewContainer = $('#imagePreviewContainer');
    const preview = $('#imagePreview');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.attr('src', e.target.result);
            previewContainer.show();
        }
        
        reader.readAsDataURL(file);
    } else {
        previewContainer.hide();
        preview.attr('src', '#');
    }
});
    </script>
    <script>
        $(document).ready(function() {
            const isSubmitted = <?php echo ($profile && $profile['updated']) ? 'true' : 'false'; ?>;
    
            if (isSubmitted) {
                disableAllInputs();
                $('#submitBtn').prop('disabled', true).text('Profile Submitted');
            }
            // Changed the button type to "button" to prevent direct submission
            $('#submitBtn').click(function(e) {
                e.preventDefault();
                
                // Check if all required fields are filled
                let isValid = true;
                $('#profileForm [required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                if (!isValid) {
                    // alert('Please fill in all required fields.');
                    return;
                }
                
                // Gather all form data for display in the modal
                let formData = {};
                $('#profileForm').serializeArray().forEach(function(item) {
                    formData[item.name] = item.value;
                });
                
                let profilePicDisplay = 'No image selected';
if ($('#profile_picture')[0].files[0]) {
    profilePicDisplay = `<img src="${URL.createObjectURL($('#profile_picture')[0].files[0])}" 
                        style="max-width: 200px; max-height: 200px; display: block; margin-top: 10px;">`;
}
                
                // Format the data for display
                let displayContent = `
                    <p><strong>Surname:</strong> ${formData.surname || ''}</p>
                    <p><strong>First Name:</strong> ${formData.firstname || ''}</p>
                    <p><strong>Other Names:</strong> ${formData.other_names || 'N/A'}</p>
                    <p><strong>Date of Birth:</strong> ${formData.date_of_birth || ''}</p>
                    <p style="text-transform: capitalize;"><strong>Gender:</strong> ${formData.gender || ''}</p>
                    <p><strong>Occupation:</strong> ${formData.occupation || ''}</p>
                    <p><strong>Highest Qualification:</strong> ${formData.highest_qualification || ''}</p>
                    <p><strong>State:</strong> ${formData.state || ''}</p>
                    <p><strong>LGA:</strong> ${formData.lga || ''}</p>
                    <p><strong>Street Address:</strong> ${formData.street_address || ''}</p>
                    <p><strong>Profile Picture:</strong></p>${profilePicDisplay}
                `;
                
                // Display in modal
                $('#confirmationContent').html(displayContent);
                $('#confirmationModal').modal('show');
            });

            function disableAllInputs() {
        $('#profileForm :input').not('[type="hidden"]').prop('disabled', true);
        $('#profile_picture').prop('disabled', true);
    }
            
            // Handle the final submission
            $('#confirmSubmit').click(function() {
    const formData = new FormData(document.getElementById('profileForm'));
    const confirmBtn = $(this);
    const originalBtnText = confirmBtn.html();
    const modal = $('#confirmationModal'); // Your modal selector
    
    // Set loading state
    confirmBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...
    `);

    $.ajax({
        url: $('#profileForm').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // 1. Disable form inputs
            disableAllInputs();
            
            // 2. Update button (inside modal)
            confirmBtn.prop('disabled', true).html(`
                <i class="bi bi-check-circle-fill"></i> Submitted Successfully
            `);
            
            // 3. Close the modal
            modal.modal('hide');
            
            // 4. Show existing success alert for 3 seconds
            const successAlert = $('#successAlert');
            successAlert.fadeIn();
            
            setTimeout(() => {
                successAlert.fadeOut();
            }, 3000);
        },
        error: function(xhr) {
            // Error handling
            alert('Error: ' + (xhr.responseText || 'Submission failed'));
            confirmBtn.prop('disabled', false).html(originalBtnText);
        }
    });
});
            
            // Update LGA options when state changes
            $('#state').change(function() {
                let state = $(this).val();
                let lgaSelect = $('#lga');
                
                lgaSelect.empty();
                lgaSelect.append('<option value="">Select LGA</option>');
                
                if (state) {
                    // This assumes you have a states_lgas object available in JavaScript
                    // You might need to echo this from PHP to JavaScript
                    let lgAs = <?php echo json_encode($states_lgas); ?>[state];
                    
                    if (lgAs) {
                        lgAs.forEach(function(lga) {
                            lgaSelect.append(`<option value="${lga}">${lga}</option>`);
                        });
                    }
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cache DOM elements
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleButton = document.getElementById('toggleSidebar');
            const stateSelect = document.getElementById('state');
            const lgaSelect = document.getElementById('lga');
            const profileForm = document.querySelector('form');
            const profilePictureInput = document.getElementById('profile_picture');
            const dobInput = document.getElementById('date_of_birth');
            const userProfileImage = document.querySelector('.user-profile img');
            const submitButton = document.getElementById('submitBtn');
            const ageError = document.getElementById('ageError');


function validateDateOfBirth() {
    const selectedDate = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - selectedDate.getFullYear();
    
    // Adjust age if birthday hasn't occurred yet this year
    const monthDiff = today.getMonth() - selectedDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < selectedDate.getDate())) {
        age--;
    }
    
    if (!dobInput.value || age < 18 || age > 100) {
        ageError.style.display = 'block';
        submitButton.disabled = true;
        return false;
    }
    
    ageError.style.display = 'none';
    submitButton.disabled = false;
    return true;
}

// Event listeners for real-time validation
dobInput.addEventListener('change', validateDateOfBirth);
dobInput.addEventListener('blur', validateDateOfBirth); // Triggers when leaving the field

// Optional: Validate on input (for real-time feedback)
dobInput.addEventListener('input', function() {
    if (dobInput.value) { // Only validate if there's a value
        validateDateOfBirth();
    }
});

            // Sidebar functionality
            function toggleSidebar() {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('expanded');
                mainContent.classList.toggle('expanded');
            }

            // Tooltip functionality
            function showTooltip(element, message) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip fade-in';
                tooltip.innerText = message;
                document.body.appendChild(tooltip);

                const rect = element.getBoundingClientRect();
                tooltip.style.left = `${rect.left + window.scrollX + element.offsetWidth / 2 - tooltip.offsetWidth / 2}px`;
                tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 5}px`;

                element.addEventListener('mouseleave', () => tooltip.remove(), { once: true });
            }

            // State and LGA handling
            function updateLGAOptions() {
                const selectedState = stateSelect.value;
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                
                if (selectedState && statesLGAs[selectedState]) {
                    const fragment = document.createDocumentFragment();
                    statesLGAs[selectedState].forEach(lga => {
                        const option = new Option(lga, lga);
                        fragment.appendChild(option);
                    });
                    lgaSelect.appendChild(fragment);
                }
            }

            // Form validation
            function validateForm(event) {
                const requiredFields = profileForm.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    const isFieldValid = field.value.trim() !== '';
                    field.classList.toggle('is-invalid', !isFieldValid);
                    if (!isFieldValid) isValid = false;
                });

                if (!isValid) {
                    event.preventDefault();
                    showNotification('Please fill in all required fields', 'error');
                }
            }

            // Profile picture preview
            function handleProfilePictureChange(event) {
                const file = event.target.files[0];
                if (file) {
                    if (!file.type.startsWith('image/')) {
                        showNotification('Please select an image file', 'error');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        userProfileImage.src = e.target.result;
                        // showNotification('Profile picture updated', 'success');
                    };
                    reader.readAsDataURL(file);
                }
            }

            // Notification system
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification ${type} fade-in`;
                notification.textContent = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('fade-out');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }

            // Event Listeners
            toggleButton.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent event from bubbling up
                toggleSidebar();
            });
            
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.addEventListener('mouseenter', () => {
                    if (!sidebar.classList.contains('expanded')) {
                        showTooltip(link, link.querySelector('.sidebar-text').innerText);
                    }
                });
            });

            stateSelect.addEventListener('change', updateLGAOptions);
            profileForm.addEventListener('submit', validateForm);
            profilePictureInput.addEventListener('change', handleProfilePictureChange);

            // Form field validation
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('invalid', (e) => {
                    e.preventDefault();
                    input.classList.add('is-invalid');
                });

                input.addEventListener('input', () => {
                    if (input.checkValidity()) {
                        input.classList.remove('is-invalid');
                    }
                });
            });

            // Add custom styles for validation
            const style = document.createElement('style');
            style.textContent = `
                .notification {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 15px 25px;
                    border-radius: 5px;
                    color: white;
                    z-index: 1000;
                }
                .notification.success { background-color: #28a745; }
                .notification.error { background-color: #dc3545; }
                .notification.info { background-color: #17a2b8; }
                .fade-in { animation: fadeIn 0.3s ease-in; }
                .fade-out { animation: fadeOut 0.3s ease-out; }
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
            `;
            document.head.appendChild(style);

            // Add this new function for handling clicks outside sidebar
            function handleOutsideClick(event) {
                const sidebar = document.getElementById('sidebar');
                const toggleButton = document.getElementById('toggleSidebar');
                
                // Check if sidebar is expanded and click is outside sidebar and not on toggle button
                if (sidebar.classList.contains('expanded') && 
                    !sidebar.contains(event.target) && 
                    !toggleButton.contains(event.target)) {
                    sidebar.classList.remove('expanded');
                    sidebar.classList.add('hidden');
                    mainContent.classList.remove('expanded');
                }
            }

            // Add click event listener to document
            document.addEventListener('click', handleOutsideClick);
            
            // Add touch event listener for mobile devices
            document.addEventListener('touchstart', handleOutsideClick);

            // Prevent event propagation from sidebar to avoid closing when clicking inside
            sidebar.addEventListener('click', (event) => {
                event.stopPropagation();
            });
            
            sidebar.addEventListener('touchstart', (event) => {
                event.stopPropagation();
            });
        });
    </script>

    <script>
        // Add custom form validation styles
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.classList.add('is-invalid');
            });

            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    </script>
    <script>
// States and LGAs data object
const statesLGAs = {
    'Abia': ['Aba North', 'Aba South', 'Arochukwu', 'Bende', 'Ikwuano', 'Isiala Ngwa North', 'Isiala Ngwa South', 'Isuikwuato', 'Obi Ngwa', 'Ohafia', 'Osisioma', 'Ugwunagbo', 'Ukwa East', 'Ukwa West', 'Umuahia North', 'Umuahia South', 'Umu Nneochi'],
    'Adamawa': ['Demsa', 'Fufure', 'Ganye', 'Girei', 'Gombi', 'Guyuk', 'Hong', 'Jada', 'Lamurde', 'Madagali', 'Maiha', 'Mayo Belwa', 'Michika', 'Mubi North', 'Mubi South', 'Numan', 'Shelleng', 'Song', 'Toungo', 'Yola North', 'Yola South'],
    'Akwa Ibom': ['Abak', 'Eastern Obolo', 'Eket', 'Esit Eket', 'Essien Udim', 'Etim Ekpo', 'Etinan', 'Ibeno', 'Ibesikpo Asutan', 'Ibiono Ibom', 'Ika', 'Ikono', 'Ikot Abasi', 'Ikot Ekpene', 'Ini', 'Itu', 'Mbo', 'Mkpat Enin', 'Nsit Atai', 'Nsit Ibom', 'Nsit Ubium', 'Obot Akara', 'Okobo', 'Onna', 'Oron', 'Oruk Anam', 'Udung Uko', 'Ukanafun', 'Uruan', 'Urue Offong Oruko', 'Uyo'],
    'Anambra': ['Aguata', 'Anambra East', 'Anambra West', 'Anaocha', 'Awka North', 'Awka South', 'Ayamelum', 'Dunukofia', 'Ekwusigo', 'Idemili North', 'Idemili South', 'Ihiala', 'Njikoka', 'Nnewi North', 'Nnewi South', 'Ogbaru', 'Onitsha North', 'Onitsha South', 'Orumba North', 'Orumba South', 'Oyi'],
    'Bauchi': ['Alkaleri', 'Bauchi', 'Bogoro', 'Damban', 'Darazo', 'Dass', 'Gamawa', 'Ganjuwa', 'Giade', 'Itas/Gadau', 'Jama\'are', 'Katagum', 'Kirfi', 'Misau', 'Ningi', 'Shira', 'Tafawa Balewa', 'Toro', 'Warji', 'Zaki'],
    'Bayelsa': ['Brass', 'Ekeremor', 'Kolokuma/Opokuma', 'Nembe', 'Ogbia', 'Sagbama', 'Southern Ijaw', 'Yenagoa'],
    'Benue': ['Ado', 'Agatu', 'Apa', 'Buruku', 'Gboko', 'Guma', 'Gwer East', 'Gwer West', 'Katsina-Ala', 'Konshisha', 'Kwande', 'Logo', 'Makurdi', 'Obi', 'Ogbadibo', 'Ohimini', 'Oju', 'Okpokwu', 'Otukpo', 'Tarka', 'Ukum', 'Ushongo', 'Vandeikya'],
    'Borno': ['Abadam', 'Askira/Uba', 'Bama', 'Bayo', 'Biu', 'Chibok', 'Damboa', 'Dikwa', 'Gubio', 'Guzamala', 'Gwoza', 'Hawul', 'Jere', 'Kaga', 'Kala/Balge', 'Konduga', 'Kukawa', 'Kwaya Kusar', 'Mafa', 'Magumeri', 'Maiduguri', 'Marte', 'Mobbar', 'Monguno', 'Ngala', 'Nganzai', 'Shani'],
    'Cross River': ['Abi', 'Akamkpa', 'Akpabuyo', 'Bakassi', 'Bekwarra', 'Biase', 'Boki', 'Calabar Municipal', 'Calabar South', 'Etung', 'Ikom', 'Obanliku', 'Obubra', 'Obudu', 'Odukpani', 'Ogoja', 'Yakuur', 'Yala'],
    'Delta': ['Aniocha North', 'Aniocha South', 'Bomadi', 'Burutu', 'Ethiope East', 'Ethiope West', 'Ika North East', 'Ika South', 'Isoko North', 'Isoko South', 'Ndokwa East', 'Ndokwa West', 'Okpe', 'Oshimili North', 'Oshimili South', 'Patani', 'Sapele', 'Udu', 'Ughelli North', 'Ughelli South', 'Ukwuani', 'Uvwie', 'Warri North', 'Warri South', 'Warri South West'],
    'Ebonyi': ['Abakaliki', 'Afikpo North', 'Afikpo South', 'Ebonyi', 'Ezza North', 'Ezza South', 'Ikwo', 'Ishielu', 'Ivo', 'Izzi', 'Ohaozara', 'Ohaukwu', 'Onicha'],
    'Edo': ['Akoko-Edo', 'Egor', 'Esan Central', 'Esan North-East', 'Esan South-East', 'Esan West', 'Etsako Central', 'Etsako East', 'Etsako West', 'Igueben', 'Ikpoba-Okha', 'Oredo', 'Orhionmwon', 'Ovia North-East', 'Ovia South-West', 'Owan East', 'Owan West', 'Uhunmwonde'],
    'Ekiti': ['Ado Ekiti', 'Efon', 'Ekiti East', 'Ekiti South-West', 'Ekiti West', 'Emure', 'Gbonyin', 'Ido Osi', 'Ijero', 'Ikere', 'Ikole', 'Ilejemeje', 'Irepodun/Ifelodun', 'Ise/Orun', 'Moba', 'Oye'],
    'Enugu': ['Aninri', 'Awgu', 'Enugu East', 'Enugu North', 'Enugu South', 'Ezeagu', 'Igbo Etiti', 'Igbo Eze North', 'Igbo Eze South', 'Isi Uzo', 'Nkanu East', 'Nkanu West', 'Nsukka', 'Oji River', 'Udenu', 'Udi', 'Uzo Uwani'],
    'Gombe': ['Akko', 'Balanga', 'Billiri', 'Dukku', 'Funakaye', 'Gombe', 'Kaltungo', 'Kwami', 'Nafada', 'Shongom', 'Yamaltu/Deba'],
    'Imo': ['Aboh Mbaise', 'Ahiazu Mbaise', 'Ehime Mbano', 'Ezinihitte', 'Ideato North', 'Ideato South', 'Ihitte/Uboma', 'Ikeduru', 'Isiala Mbano', 'Isu', 'Mbaitoli', 'Ngor Okpala', 'Njaba', 'Nkwerre', 'Nwangele', 'Obowo', 'Oguta', 'Ohaji/Egbema', 'Okigwe', 'Onuimo', 'Orlu', 'Orsu', 'Oru East', 'Oru West', 'Owerri Municipal', 'Owerri North', 'Owerri West'],
    'Jigawa': ['Auyo', 'Babura', 'Biriniwa', 'Birnin Kudu', 'Buji', 'Dutse', 'Gagarawa', 'Garki', 'Gumel', 'Guri', 'Gwaram', 'Gwiwa', 'Hadejia', 'Jahun', 'Kafin Hausa', 'Kaugama', 'Kazaure', 'Kiri Kasama', 'Kiyawa', 'Maigatari', 'Malam Madori', 'Miga', 'Ringim', 'Roni', 'Sule Tankarkar', 'Taura', 'Yankwashi'],
    'Kaduna': ['Birnin Gwari', 'Chikun', 'Giwa', 'Igabi', 'Ikara', 'Jaba', 'Jema\'a', 'Kachia', 'Kaduna North', 'Kaduna South', 'Kagarko', 'Kajuru', 'Kaura', 'Kauru', 'Kubau', 'Kudan', 'Lere', 'Makarfi', 'Sabon Gari', 'Sanga', 'Soba', 'Zangon Kataf', 'Zaria'],
    'Kano': ['Ajingi', 'Albasu', 'Bagwai', 'Bebeji', 'Bichi', 'Bunkure', 'Dala', 'Dambatta', 'Dawakin Kudu', 'Dawakin Tofa', 'Doguwa', 'Fagge', 'Gabasawa', 'Garko', 'Garun Mallam', 'Gaya', 'Gezawa', 'Gwale', 'Gwarzo', 'Kabo', 'Kano Municipal', 'Karaye', 'Kibiya', 'Kiru', 'Kumbotso', 'Kunchi', 'Kura', 'Madobi', 'Makoda', 'Minjibir', 'Nasarawa', 'Rano', 'Rimin Gado', 'Rogo', 'Shanono', 'Sumaila', 'Takai', 'Tarauni', 'Tofa', 'Tsanyawa', 'Tudun Wada', 'Ungogo', 'Warawa', 'Wudil'],
    'Katsina': ['Bakori', 'Batagarawa', 'Batsari', 'Baure', 'Bindawa', 'Charanchi', 'Dandume', 'Danja', 'Dan Musa', 'Daura', 'Dutsi', 'Dutsin Ma', 'Faskari', 'Funtua', 'Ingawa', 'Jibia', 'Kafur', 'Kaita', 'Kankara', 'Kankia', 'Katsina', 'Kurfi', 'Kusada', 'Mai\'Adua', 'Malumfashi', 'Mani', 'Mashi', 'Matazu', 'Musawa', 'Rimi', 'Sabuwa', 'Safana', 'Sandamu', 'Zango'],
    'Kebbi': ['Aleiro', 'Arewa Dandi', 'Argungu', 'Augie', 'Bagudo', 'Birnin Kebbi', 'Bunza', 'Dandi', 'Fakai', 'Gwandu', 'Jega', 'Kalgo', 'Koko/Besse', 'Maiyama', 'Ngaski', 'Sakaba', 'Shanga', 'Suru', 'Wasagu/Danko', 'Yauri', 'Zuru'],
    'Kogi': ['Adavi', 'Ajaokuta', 'Ankpa', 'Bassa', 'Dekina', 'Ibaji', 'Idah', 'Igalamela Odolu', 'Ijumu', 'Kabba/Bunu', 'Kogi', 'Lokoja', 'Mopa Muro', 'Ofu', 'Ogori/Magongo', 'Okehi', 'Okene', 'Olamaboro', 'Omala', 'Yagba East', 'Yagba West'],
    'Kwara': ['Asa', 'Baruten', 'Edu', 'Ekiti', 'Ifelodun', 'Ilorin East', 'Ilorin South', 'Ilorin West', 'Irepodun', 'Isin', 'Kaiama', 'Moro', 'Offa', 'Oke Ero', 'Oyun', 'Pategi'],
    'Lagos': ['Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa', 'Badagry', 'Epe', 'Eti Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye', 'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island', 'Lagos Mainland', 'Mushin', 'Ojo', 'Oshodi-Isolo', 'Shomolu', 'Surulere'],
    'Nasarawa': ['Akwanga', 'Awe', 'Doma', 'Karu', 'Keana', 'Keffi', 'Kokona', 'Lafia', 'Nasarawa', 'Nasarawa Egon', 'Obi', 'Toto', 'Wamba'],
    'Niger': ['Agaie', 'Agwara', 'Bida', 'Borgu', 'Bosso', 'Chanchaga', 'Edati', 'Gbako', 'Gurara', 'Katcha', 'Kontagora', 'Lapai', 'Lavun', 'Magama', 'Mariga', 'Mashegu', 'Mokwa', 'Moya', 'Paikoro', 'Rafi', 'Rijau', 'Shiroro', 'Suleja', 'Tafa', 'Wushishi'],
    'Ogun': ['Abeokuta North', 'Abeokuta South', 'Ado-Odo/Ota', 'Egbado North', 'Egbado South', 'Ewekoro', 'Ifo', 'Ijebu East', 'Ijebu North', 'Ijebu North East', 'Ijebu Ode', 'Ikenne', 'Imeko Afon', 'Ipokia', 'Obafemi Owode', 'Odeda', 'Odogbolu', 'Ogun Waterside', 'Remo North', 'Shagamu'],
    'Ondo': ['Akoko North-East', 'Akoko North-West', 'Akoko South-East', 'Akoko South-West', 'Akure North', 'Akure South', 'Ese Odo', 'Idanre', 'Ifedore', 'Ilaje', 'Ile Oluji/Okeigbo', 'Irele', 'Odigbo', 'Okitipupa', 'Ondo East', 'Ondo West', 'Ose', 'Owo'],
    'Osun': ['Atakunmosa East', 'Atakunmosa West', 'Aiyedaade', 'Aiyedire', 'Boluwaduro', 'Boripe', 'Ede North', 'Ede South', 'Egbedore', 'Ejigbo', 'Ife Central', 'Ife East', 'Ife North', 'Ife South', 'Ifedayo', 'Ifelodun', 'Ila', 'Ilesa East', 'Ilesa West', 'Irepodun', 'Irewole', 'Isokan', 'Iwo', 'Obokun', 'Odo Otin', 'Ola Oluwa', 'Olorunda', 'Oriade', 'Orolu', 'Osogbo'],
    'Oyo': ['Afijio', 'Akinyele', 'Atiba', 'Atisbo', 'Egbeda', 'Ibadan North', 'Ibadan North-East', 'Ibadan North-West', 'Ibadan South-East', 'Ibadan South-West', 'Ibarapa Central', 'Ibarapa East', 'Ibarapa North', 'Ido', 'Irepo', 'Iseyin', 'Itesiwaju', 'Iwajowa', 'Kajola', 'Lagelu', 'Ogbomosho North', 'Ogbomosho South', 'Ogo Oluwa', 'Olorunsogo', 'Oluyole', 'Ona Ara', 'Orelope', 'Ori Ire', 'Oyo East', 'Oyo West', 'Saki East', 'Saki West', 'Surulere'],
    'Plateau': ['Bokkos', 'Barkin Ladi', 'Bassa', 'Jos East', 'Jos North', 'Jos South', 'Kanam', 'Kanke', 'Langtang North', 'Langtang South', 'Mangu', 'Mikang', 'Pankshin', 'Qua\'an Pan', 'Riyom', 'Shendam', 'Wase'],
    'Rivers': ['Abua/Odual', 'Ahoada East', 'Ahoada West', 'Akuku-Toru', 'Andoni', 'Asari-Toru', 'Bonny', 'Degema', 'Eleme', 'Emohua', 'Etche', 'Gokana', 'Ikwerre', 'Khana', 'Obio/Akpor', 'Ogba/Egbema/Ndoni', 'Ogu/Bolo', 'Okrika', 'Omuma', 'Opobo/Nkoro', 'Oyigbo', 'Port Harcourt', 'Tai'],
    'Sokoto': ['Binji', 'Bodinga', 'Dange Shuni', 'Gada', 'Goronyo', 'Gudu', 'Gwadabawa', 'Illela', 'Isa', 'Kebbe', 'Kware', 'Rabah', 'Sabon Birni', 'Shagari', 'Silame', 'Sokoto North', 'Sokoto South', 'Tambuwal', 'Tangaza', 'Tureta', 'Wamako', 'Wurno', 'Yabo'],
    'Taraba': ['Ardo Kola', 'Bali', 'Donga', 'Gashaka', 'Gassol', 'Ibi', 'Jalingo', 'Karim Lamido', 'Kurmi', 'Lau', 'Sardauna', 'Takum', 'Ussa', 'Wukari', 'Yorro', 'Zing'],
    'Yobe': ['Bade', 'Bursari', 'Damaturu', 'Fika', 'Fune', 'Geidam', 'Gujba', 'Gulani', 'Jakusko', 'Karasuwa', 'Machina', 'Nangere', 'Nguru', 'Potiskum', 'Tarmuwa', 'Yunusari', 'Yusufari'],
    'Zamfara': ['Anka', 'Bakura', 'Birnin Magaji/Kiyaw', 'Bukkuyum', 'Bungudu', 'Gummi', 'Gusau', 'Kaura Namoda', 'Maradun', 'Maru', 'Shinkafi', 'Talata Mafara', 'Tsafe', 'Zurmi']
    // Add other states as needed from your data
};

document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('state');
    const lgaSelect = document.getElementById('lga');

    // Populate states dropdown
    Object.keys(statesLGAs).forEach(state => {
        const option = new Option(state, state);
        stateSelect.add(option);
    });

    // Function to update LGA options based on selected state
    function updateLGAOptions() {
        const selectedState = stateSelect.value;

        // Clear existing LGA options
        lgaSelect.innerHTML = '<option value="">Select LGA</option>';

        // If a state is selected, populate LGA options
        if (selectedState && statesLGAs[selectedState]) {
            statesLGAs[selectedState].forEach(lga => {
                const option = new Option(lga, lga);
                lgaSelect.add(option);
            });
        }
    }

    // Add event listener for state selection change
    stateSelect.addEventListener('change', updateLGAOptions);

    // Initial population of LGAs if state is already selected
    if (stateSelect.value) {
        updateLGAOptions();
    }
});
</script>
</body>

</html>
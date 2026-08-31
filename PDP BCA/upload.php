<?php

require_once 'config/database.php';


// =====================================================
// KONFIGURASI
// =====================================================

$uploadDirectory = __DIR__ . '/uploads/documents/';

$maxFileSize = 5 * 1024 * 1024; // 5 MB

$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png'
];

$allowedExtensions = [
    'pdf',
    'jpg',
    'jpeg',
    'png'
];


// =====================================================
// HANYA MENERIMA METHOD POST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: index.php');
    exit;
}


// =====================================================
// AMBIL DATA FORM
// =====================================================

$nama = trim($_POST['nama'] ?? '');
$nik = trim($_POST['nik'] ?? '');
$noHp = trim($_POST['no_hp'] ?? '');
$dealer = trim($_POST['dealer'] ?? '');


// =====================================================
// VALIDASI DATA
// =====================================================

$errors = [];


// Validasi nama
if ($nama === '') {

    $errors[] = 'Nama wajib diisi.';

}


// Validasi NIK
if ($nik === '') {

    $errors[] = 'NIK wajib diisi.';

} elseif (!preg_match('/^[0-9]{16}$/', $nik)) {

    $errors[] = 'NIK harus terdiri dari 16 digit angka.';

}


// Validasi nomor HP
if ($noHp === '') {

    $errors[] = 'Nomor HP wajib diisi.';

} elseif (!preg_match('/^[0-9+\-\s]{10,20}$/', $noHp)) {

    $errors[] = 'Nomor HP tidak valid.';

}


// Validasi dealer
if ($dealer === '') {

    $errors[] = 'Dealer wajib diisi.';

}


// =====================================================
// VALIDASI FILE
// =====================================================

$documents = [
    'ktp' => 'KTP',
    'spk' => 'SPK',
    'bukti_bayar' => 'Bukti Bayar Tanda Jadi'
];


foreach ($documents as $inputName => $documentName) {

    if (
        !isset($_FILES[$inputName]) ||
        $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE
    ) {

        $errors[] =
            $documentName . ' wajib diupload.';

        continue;
    }


    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {

        $errors[] =
            'Terjadi kesalahan saat upload ' .
            $documentName . '.';

        continue;
    }


    $file = $_FILES[$inputName];


    // Validasi ukuran
    if ($file['size'] > $maxFileSize) {

        $errors[] =
            $documentName .
            ' tidak boleh lebih dari 5 MB.';

    }


    // Validasi extension
    $extension = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );


    if (!in_array(
        $extension,
        $allowedExtensions,
        true
    )) {

        $errors[] =
            $documentName .
            ' memiliki format file yang tidak diperbolehkan.';

    }


    // Validasi MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeType = $finfo->file(
        $file['tmp_name']
    );


    if (!in_array(
        $mimeType,
        $allowedMimeTypes,
        true
    )) {

        $errors[] =
            $documentName .
            ' memiliki tipe file yang tidak valid.';

    }
}


// =====================================================
// JIKA ADA ERROR
// =====================================================

if (!empty($errors)) {

    session_start();

    $_SESSION['upload_errors'] = $errors;

    header('Location: index.php');

    exit;
}


// =====================================================
// BUAT FOLDER UPLOAD
// =====================================================

if (!is_dir($uploadDirectory)) {

    if (!mkdir(
        $uploadDirectory,
        0755,
        true
    )) {

        die(
            'Folder upload tidak dapat dibuat.'
        );
    }
}


// =====================================================
// PROSES DATABASE
// =====================================================

$uploadedFiles = [];

try {

    // Mulai transaction
    $pdo->beginTransaction();


    // =================================================
    // 1. SIMPAN / UPDATE CUSTOMER
    // =================================================

    $customerQuery = "
        SELECT id
        FROM customers
        WHERE nik = ?
        LIMIT 1
    ";

    $customerStatement =
        $pdo->prepare($customerQuery);

    $customerStatement->execute([
        $nik
    ]);

    $customer = $customerStatement->fetch();


    if ($customer) {

        // Customer sudah ada
        $customerId = $customer['id'];


        $updateCustomer = "
            UPDATE customers

            SET
                nama = ?,
                no_hp = ?,
                updated_at = CURRENT_TIMESTAMP

            WHERE id = ?
        ";

        $statement =
            $pdo->prepare($updateCustomer);

        $statement->execute([
            $nama,
            $noHp,
            $customerId
        ]);

    } else {

        // Customer baru
        $insertCustomer = "
            INSERT INTO customers
            (
                nik,
                nama,
                no_hp
            )

            VALUES
            (
                ?,
                ?,
                ?
            )
        ";

        $statement =
            $pdo->prepare($insertCustomer);

        $statement->execute([
            $nik,
            $nama,
            $noHp
        ]);

        $customerId =
            $pdo->lastInsertId();
    }


    // =================================================
    // 2. SIMPAN APPLICATION
    // =================================================

    $applicationQuery = "
        INSERT INTO applications
        (
            customer_id,
            dealer,
            status
        )

        VALUES
        (
            ?,
            ?,
            'submitted'
        )
    ";

    $applicationStatement =
        $pdo->prepare($applicationQuery);

    $applicationStatement->execute([
        $customerId,
        $dealer
    ]);


    $applicationId =
        $pdo->lastInsertId();


    // =================================================
    // 3. SIMPAN DOKUMEN
    // =================================================

    foreach ($documents as $inputName => $documentName) {

        $file =
            $_FILES[$inputName];


        $originalName =
            $file['name'];


        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );


        $mimeType =
            (new finfo(FILEINFO_MIME_TYPE))
                ->file($file['tmp_name']);


        $fileSize =
            $file['size'];


        // Nama file unik
        $newFileName =
            $inputName .
            '_' .
            $applicationId .
            '_' .
            bin2hex(random_bytes(8)) .
            '.' .
            $extension;


        $destination =
            $uploadDirectory .
            $newFileName;


        // Pindahkan file
        if (!move_uploaded_file(
            $file['tmp_name'],
            $destination
        )) {

            throw new Exception(
                'Gagal menyimpan file ' .
                $documentName
            );
        }


        // Simpan path untuk rollback
        $uploadedFiles[] =
            $destination;


        // Path relatif
        $relativePath =
            'uploads/documents/' .
            $newFileName;


        // Simpan informasi file ke database
        $documentQuery = "
            INSERT INTO application_documents
            (
                application_id,
                document_type,
                file_name,
                file_path,
                file_size,
                mime_type
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $documentStatement =
            $pdo->prepare(
                $documentQuery
            );


        $documentStatement->execute([
            $applicationId,
            $inputName,
            $originalName,
            $relativePath,
            $fileSize,
            $mimeType
        ]);
    }


    // =================================================
    // 4. COMMIT
    // =================================================

    $pdo->commit();


    // =================================================
    // REDIRECT SUCCESS
    // =================================================

    header(
        'Location: success.php?id=' .
        $applicationId
    );

    exit;


} catch (Throwable $e) {


    // =================================================
    // ROLLBACK DATABASE
    // =================================================

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    // =================================================
    // HAPUS FILE YANG SUDAH TERUPLOAD
    // =================================================

    foreach ($uploadedFiles as $file) {

        if (file_exists($file)) {

            unlink($file);
        }
    }


    // =================================================
    // TAMPILKAN ERROR
    // =================================================

    http_response_code(500);

    echo '
        <!DOCTYPE html>

        <html lang="id">

        <head>

            <meta charset="UTF-8">

            <title>
                Pengajuan Gagal
            </title>

        </head>

        <body>

            <h2>
                Pengajuan gagal diproses.
            </h2>

            <p>
                Silakan coba kembali.
            </p>

            <a href="index.php">
                Kembali ke Form
            </a>

        </body>

        </html>
    ';

    // Untuk development saja:
    // echo '<pre>';
    // print_r($e->getMessage());
    // echo '</pre>';

    exit;
}
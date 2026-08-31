/* =========================================================
   KONFIGURASI
========================================================= */

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

const ALLOWED_FILE_TYPES = [
    "application/pdf",
    "image/jpeg",
    "image/png"
];


/* =========================================================
   ELEMENT
========================================================= */

const form = document.getElementById("applicationForm");

const nikInput = document.getElementById("nik");
const phoneInput = document.getElementById("no_hp");

const ktpInput = document.getElementById("ktp");
const spkInput = document.getElementById("spk");
const buktiBayarInput = document.getElementById("bukti_bayar");

const ktpName = document.getElementById("ktp-name");
const spkName = document.getElementById("spk-name");
const buktiBayarName = document.getElementById("bukti-bayar-name");

const documentItems = document.querySelectorAll(".document-item");

const validationBox = document.querySelector(".validation-box");
const validationText = document.querySelector(".validation-box p");


/* =========================================================
   VALIDASI NIK
========================================================= */

nikInput.addEventListener("input", function () {

    // Hanya menerima angka
    this.value = this.value.replace(/\D/g, "");

    // Maksimal 16 digit
    if (this.value.length > 16) {
        this.value = this.value.substring(0, 16);
    }

});


/* =========================================================
   VALIDASI NOMOR HP
========================================================= */

phoneInput.addEventListener("input", function () {

    // Hanya menerima angka
    this.value = this.value.replace(/\D/g, "");

});


/* =========================================================
   EVENT UPLOAD DOKUMEN
========================================================= */

ktpInput.addEventListener("change", function () {

    handleFileUpload(
        this,
        ktpName,
        documentItems[0]
    );

});


spkInput.addEventListener("change", function () {

    handleFileUpload(
        this,
        spkName,
        documentItems[1]
    );

});


buktiBayarInput.addEventListener("change", function () {

    handleFileUpload(
        this,
        buktiBayarName,
        documentItems[2]
    );

});


/* =========================================================
   FUNCTION HANDLE FILE
========================================================= */

function handleFileUpload(input, fileNameElement, documentItem) {

    const file = input.files[0];

    if (!file) {
        return;
    }


    /* -----------------------------------------
       VALIDASI FORMAT FILE
    ----------------------------------------- */

    if (!ALLOWED_FILE_TYPES.includes(file.type)) {

        alert(
            "Format file tidak valid.\n\n" +
            "Format yang diperbolehkan:\n" +
            "PDF, JPG, JPEG, PNG."
        );

        input.value = "";

        resetDocumentStatus(
            fileNameElement,
            documentItem
        );

        updateDocumentValidation();

        return;
    }


    /* -----------------------------------------
       VALIDASI UKURAN FILE
    ----------------------------------------- */

    if (file.size > MAX_FILE_SIZE) {

        alert(
            "Ukuran file terlalu besar.\n\n" +
            "Ukuran maksimal adalah 5 MB."
        );

        input.value = "";

        resetDocumentStatus(
            fileNameElement,
            documentItem
        );

        updateDocumentValidation();

        return;
    }


    /* -----------------------------------------
       FILE VALID
    ----------------------------------------- */

    fileNameElement.textContent =
        file.name + " (" + formatFileSize(file.size) + ")";


    const status = documentItem.querySelector(
        ".document-status"
    );


    status.textContent = "Sudah Upload";

    status.classList.remove("pending");

    status.classList.add("success");


    updateDocumentValidation();

}


/* =========================================================
   RESET STATUS DOKUMEN
========================================================= */

function resetDocumentStatus(
    fileNameElement,
    documentItem
) {

    fileNameElement.textContent =
        "Belum ada file";


    const status =
        documentItem.querySelector(
            ".document-status"
        );


    status.textContent =
        "Belum Upload";


    status.classList.remove("success");

    status.classList.add("pending");

}


/* =========================================================
   FORMAT UKURAN FILE
========================================================= */

function formatFileSize(size) {

    if (size < 1024) {

        return size + " Bytes";

    }


    if (size < 1024 * 1024) {

        return (
            (size / 1024).toFixed(1)
            + " KB"
        );

    }


    return (
        (size / (1024 * 1024)).toFixed(1)
        + " MB"
    );

}


/* =========================================================
   CEK JUMLAH DOKUMEN
========================================================= */

function getUploadedDocuments() {

    let total = 0;


    if (ktpInput.files.length > 0) {
        total++;
    }


    if (spkInput.files.length > 0) {
        total++;
    }


    if (buktiBayarInput.files.length > 0) {
        total++;
    }


    return total;

}


/* =========================================================
   UPDATE VALIDASI DOKUMEN
========================================================= */

function updateDocumentValidation() {

    const uploaded =
        getUploadedDocuments();


    const total = 3;


    if (uploaded === total) {

        validationBox.classList.add("valid");


        validationText.textContent =
            "Semua dokumen telah lengkap dan siap dikirim.";


    } else {

        validationBox.classList.remove("valid");


        validationText.textContent =
            uploaded +
            " dari " +
            total +
            " dokumen telah diupload.";

    }

}


/* =========================================================
   VALIDASI FORM SEBELUM SUBMIT
========================================================= */

form.addEventListener("submit", function (event) {

    /* -----------------------------------------
       VALIDASI NIK
    ----------------------------------------- */

    if (nikInput.value.length !== 16) {

        event.preventDefault();

        alert(
            "NIK harus terdiri dari 16 digit."
        );

        nikInput.focus();

        return;
    }


    /* -----------------------------------------
       VALIDASI NOMOR HP
    ----------------------------------------- */

    if (phoneInput.value.length < 10) {

        event.preventDefault();

        alert(
            "Nomor HP tidak valid."
        );

        phoneInput.focus();

        return;
    }


    /* -----------------------------------------
       VALIDASI DOKUMEN
    ----------------------------------------- */

    const uploaded =
        getUploadedDocuments();


    if (uploaded < 3) {

        event.preventDefault();


        alert(
            "Dokumen belum lengkap.\n\n" +
            "Silakan upload KTP, SPK, dan Bukti Bayar Tanda Jadi."
        );


        updateDocumentValidation();

        return;
    }


    /* -----------------------------------------
       SEMUA VALID
    ----------------------------------------- */

    validationBox.classList.add("valid");

    validationText.textContent =
        "Data dan dokumen valid. Pengajuan sedang dikirim.";

});


/* =========================================================
   RESET FORM
========================================================= */

form.addEventListener("reset", function () {

    // Tunggu reset HTML selesai
    setTimeout(function () {

        resetDocumentStatus(
            ktpName,
            documentItems[0]
        );


        resetDocumentStatus(
            spkName,
            documentItems[1]
        );


        resetDocumentStatus(
            buktiBayarName,
            documentItems[2]
        );


        validationBox.classList.remove(
            "valid"
        );


        validationText.textContent =
            "Pastikan seluruh dokumen telah diupload sebelum mengirim pengajuan.";

    }, 50);

});
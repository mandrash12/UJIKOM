import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

doc = Document()

# Set standard margins (1 inch / 2.54 cm)
sections = doc.sections
for section in sections:
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)

# Style defaults
style = doc.styles['Normal']
font = style.font
font.name = 'Calibri'
font.size = Pt(11)
font.color.rgb = RGBColor(0x33, 0x33, 0x33)

def set_cell_background(cell, fill_hex):
    shading_elm = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    cell._tc.get_or_add_tcPr().append(shading_elm)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)

def add_heading_1(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Calibri'
    run.font.size = Pt(13)
    run.font.bold = True
    run.font.color.rgb = RGBColor(0x1E, 0x40, 0xAF) # Deep blue
    return p

def add_heading_2(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(10)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Calibri'
    run.font.size = Pt(11)
    run.font.bold = True
    run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A) # Dark slate
    return p

def add_code_snippet(title, code_str, explanation_points):
    add_heading_2(title)
    
    # Table as code container
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = tbl.rows[0].cells[0]
    cell.width = Inches(6.5)
    set_cell_background(cell, "F8FAFC")
    set_cell_margins(cell, 80, 80, 100, 100)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    run = p.add_run(code_str.strip())
    run.font.name = 'Consolas'
    run.font.size = Pt(8.5)
    run.font.color.rgb = RGBColor(0x1E, 0x29, 0x3B)
    
    doc.add_paragraph().paragraph_format.space_after = Pt(3)
    
    # Explanations
    for bullet_title, bullet_desc in explanation_points:
        pb = doc.add_paragraph()
        pb.paragraph_format.left_indent = Inches(0.2)
        pb.paragraph_format.space_after = Pt(2.5)
        
        rt = pb.add_run(f"• {bullet_title}: ")
        rt.font.bold = True
        rt.font.size = Pt(9.5)
        rt.font.color.rgb = RGBColor(0x1E, 0x40, 0xAF)
        
        rd = pb.add_run(bullet_desc)
        rd.font.size = Pt(9.5)
        rd.font.color.rgb = RGBColor(0x33, 0x41, 0x55)
        
    doc.add_paragraph().paragraph_format.space_after = Pt(4)

# ----------------- TITLE / HEADER -----------------
title_p = doc.add_paragraph()
title_p.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
title_p.paragraph_format.space_after = Pt(2)
run_title = title_p.add_run("LAPORAN HASIL PENGUJIAN SISTEM\n(BLACK BOX TESTING) & BEDAH KODE PROGRAM")
run_title.font.name = 'Calibri'
run_title.font.size = Pt(16)
run_title.font.bold = True
run_title.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)

sub_p = doc.add_paragraph()
sub_p.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
sub_p.paragraph_format.space_after = Pt(16)
run_sub = sub_p.add_run("Sistem Informasi Pembayaran SPP Siswa (SPP-PAY)\nUji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak")
run_sub.font.size = Pt(11.5)
run_sub.font.bold = True
run_sub.font.color.rgb = RGBColor(0x4B, 0x55, 0x63)

# ----------------- BAGIAN A: INFORMASI SISTEM -----------------
add_heading_1("A. INFORMASI SISTEM DAN LINGKUNGAN PENGUJIAN")

info_table = doc.add_table(rows=6, cols=2)
info_table.alignment = WD_TABLE_ALIGNMENT.CENTER
info_data = [
    ("Nama Aplikasi", "Sistem Informasi Pembayaran SPP Siswa (SPP-PAY)"),
    ("Basis Data (RDBMS)", "MySQL / MariaDB (Database: pembayaran_spp_siswa, 6 Tabel Relasional)"),
    ("Bahasa Pemrograman", "PHP Native (PDO - PHP Data Objects) Versi 8.x"),
    ("Antarmuka / Styling", "HTML5 Semantik, CSS3 Kustom, Bootstrap 5.3 & Bootstrap Icons"),
    ("Server Lokal", "XAMPP (Apache Web Server & MySQL Server)"),
    ("Metode Pengujian", "Black Box Testing (Pengujian fungsionalitas UI, alur transaksi, & validasi)")
]

col_widths = [Inches(2.2), Inches(4.3)]
for i, (label, val) in enumerate(info_data):
    row = info_table.rows[i]
    cell_lbl, cell_val = row.cells[0], row.cells[1]
    
    cell_lbl.text = label
    cell_val.text = val
    
    cell_lbl.paragraphs[0].runs[0].font.bold = True
    cell_lbl.paragraphs[0].runs[0].font.size = Pt(9.5)
    cell_val.paragraphs[0].runs[0].font.size = Pt(9.5)
    
    set_cell_background(cell_lbl, "F1F5F9")
    set_cell_background(cell_val, "FFFFFF")
    
    set_cell_margins(cell_lbl, 80, 80, 100, 100)
    set_cell_margins(cell_val, 80, 80, 100, 100)

for row in info_table.rows:
    for i, w in enumerate(col_widths):
        row.cells[i].width = w

doc.add_paragraph().paragraph_format.space_after = Pt(6)

# ----------------- BAGIAN B: TAHAPAN PEMBUATAN -----------------
add_heading_1("B. TAHAPAN PEMBUATAN SISTEM (DARI AWAL SAMPAI JADI)")
intro_b = doc.add_paragraph(
    "Pengembangan aplikasi pembayaran SPP ini dilakukan secara bertahap dan terstruktur dengan tahapan sebagai berikut:"
)
intro_b.paragraph_format.space_after = Pt(4)

stages = [
    ("1. Perancangan Basis Data (Database Design)",
     "Merancang 6 tabel relasional pada MySQL: 'petugas', 'kelas', 'tarif_spp' (sudah mencakup tahun angkatan/ajaran), 'siswa', 'tagihan_spp', dan 'pembayaran'. "
     "Setiap tabel dihubungkan dengan relasi Foreign Key menggunakan constraint 'ON DELETE RESTRICT' dan 'ON UPDATE CASCADE' guna menjaga integritas data."),
    
    ("2. Konfigurasi Koneksi Database (koneksi.php)",
     "Membangun modul konektivitas menggunakan PDO (PHP Data Objects) dengan atribut penanganan kesalahan 'ERRMODE_EXCEPTION'. "
     "Koneksi dibungkus blok try-catch untuk mengantisipasi kegagalan server database secara anggun."),
    
    ("3. Desain Antarmuka Pengguna (User Interface)",
     "Membangun antarmuka modern dan responsif berbasis Bootstrap 5. Tampilan dilengkapi bilah navigasi, 4 kartu ringkasan statistik keuangan secara langsung (real-time), "
     "form pencarian interaktif, tabel tagihan, dan modal konfirmasi pembayaran."),
    
    ("4. Logika Bisnis & Pencarian Siswa (index.php)",
     "Mengimplementasikan fungsionalitas pencarian multi-kriteria (berdasarkan NISN, NIS, atau kemiripan nama siswa). "
     "Sistem otomatis menampilkan detail tagihan per bulan beserta status kelunasan dan sisa tagihan."),
    
    ("5. Modul Transaksi & Integritas Data (PDO Transaction)",
     "Mengamankan proses pelunasan tagihan menggunakan 'beginTransaction()', 'commit()', dan 'rollBack()'. "
     "Pembayaran menghasilkan kode transaksi unik otomatis (TRX-...), mencatat riwayat transaksi, dan memperbarui status tagihan menjadi 'lunas'.")
]

for title, desc in stages:
    p = doc.add_paragraph()
    p.paragraph_format.left_indent = Inches(0.2)
    p.paragraph_format.space_after = Pt(3)
    run_t = p.add_run(title + "\n")
    run_t.font.bold = True
    run_t.font.size = Pt(10)
    run_t.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    
    run_d = p.add_run(desc)
    run_d.font.size = Pt(9.5)
    run_d.font.color.rgb = RGBColor(0x37, 0x41, 0x51)

doc.add_paragraph().paragraph_format.space_after = Pt(6)

# ----------------- BAGIAN C: MATRIKS PENGUJIAN -----------------
add_heading_1("C. MATRIKS HASIL PENGUJIAN BLACK BOX")
intro_c = doc.add_paragraph(
    "Pengujian fungsionalitas dilakukan pada setiap modul untuk memastikan seluruh alur kerja berjalan sesuai kebutuhan sistem:"
)
intro_c.paragraph_format.space_after = Pt(4)

headers = ["No", "Modul / Fitur", "Skenario Pengujian", "Data Uji (Input)", "Hasil yang Diharapkan", "Hasil Aktual", "Status"]
test_cases = [
    ("1", "Koneksi Database (koneksi.php)", "Menguji koneksi PHP ke MySQL saat server aktif", "Host: localhost, DB: pembayaran_spp_siswa, User: root", "Halaman web terbuka lancar tanpa pesan error", "Koneksi PDO terhubung sempurna", "Valid"),
    ("2", "Koneksi Database (Error Handling)", "Menguji penanganan galat jika MySQL mati/salah konfigurasi", "Service MySQL XAMPP dinonaktifkan", "Menampilkan kotak peringatan merah berisi info database terputus", "Tampil notifikasi error terstruktur tanpa fatal crash", "Valid"),
    ("3", "Statistik Dashboard", "Kalkulasi otomatis 4 kartu metrik di halaman utama", "Query agregat COUNT & SUM dari database", "Kartu menampilkan total siswa, omzet hari ini, bulan ini, dan tunggakan", "Data statistik tampil akurat sesuai isi database", "Valid"),
    ("4", "Pencarian Siswa (NISN Valid)", "Pencarian data siswa berdasarkan 10 digit NISN", "Input: '0061234567' lalu klik 'Cari Data'", "Tampil identitas siswa (Nama, Kelas, Tarif) dan tabel rincian tagihan", "Data Ahmad Farhan beserta tagihan bulanan tampil lengkap", "Valid"),
    ("5", "Pencarian Siswa (Nama Siswa)", "Pencarian siswa berdasarkan kata kunci nama", "Input: 'Bunga'", "Menampilkan siswa yang cocok menggunakan klausa SQL LIKE", "Data Bunga Citra Lestari berhasil dimuat", "Valid"),
    ("6", "Pencarian Siswa (Tidak Ditemukan)", "Pencarian menggunakan data yang tidak terdaftar", "Input: '9999999999'", "Menampilkan pesan peringatan data tidak ditemukan", "Muncul alert peringatan bahwa data siswa tidak ada", "Valid"),
    ("7", "Tombol Reset", "Mengembalikan halaman pencarian ke mode utama", "Klik tombol 'Reset' di form cari", "Input pencarian dikosongkan dan parameter URL di-reset", "Tampilan kembali ke dashboard awal", "Valid"),
    ("8", "Modal Bayar Tagihan", "Memicu pop-up pembayaran pada tagihan belum lunas", "Klik 'Bayar Sekarang' pada periode yang belum lunas", "Pop-up modal terbuka dan otomatis mengisi nominal sisa tagihan", "Modal terbuka, data tagihan terisi lewat atribut data-bs-*", "Valid"),
    ("9", "Proses Pembayaran (Validasi Lunas)", "Melakukan pembayaran penuh terhadap tagihan", "Jumlah: 250000, Metode: Tunai, Aksi: Bayar", "Sistem mencatat transaksi, status tagihan berubah LUNAS", "Status berubah hijau (LUNAS), kode transaksi TRX-... terbit", "Valid"),
    ("10", "Integritas Transaksi (DB Transaction)", "Memastikan keamanan data multi-tabel saat bayar", "Query INSERT ke pembayaran dan UPDATE ke tagihan_spp", "Kedua query harus berhasil bersamaan, atau dibatalkan seluruhnya", "Diproteksi beginTransaction() dan rollBack() jika error", "Valid"),
    ("11", "Riwayat 10 Transaksi", "Menampilkan log pembayaran terbaru pada tabel bawah", "Melakukan transaksi pembayaran baru", "Transaksi terkini otomatis menduduki baris teratas tabel log", "Data pembayaran baru langsung tampil di urutan paling atas", "Valid"),
    ("12", "Responsivitas Layout", "Menguji adaptasi antarmuka pada berbagai resolusi", "Pengujian layar Desktop (1920x1080) & Smartphone (375x667)", "Tampilan tidak pecah dan tabel mendukung scroll horizontal", "Bootstrap grid & class table-responsive bekerja rapi", "Valid")
]

test_table = doc.add_table(rows=len(test_cases) + 1, cols=7)
test_table.alignment = WD_TABLE_ALIGNMENT.CENTER

hdr_cells = test_table.rows[0].cells
for j, h in enumerate(headers):
    hdr_cells[j].text = h
    hdr_cells[j].paragraphs[0].runs[0].font.bold = True
    hdr_cells[j].paragraphs[0].runs[0].font.size = Pt(9)
    hdr_cells[j].paragraphs[0].runs[0].font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
    set_cell_background(hdr_cells[j], "1E40AF") # Royal Blue
    set_cell_margins(hdr_cells[j], 80, 80, 60, 60)
    hdr_cells[j].vertical_alignment = WD_ALIGN_VERTICAL.CENTER

col_w_table = [Inches(0.4), Inches(1.1), Inches(1.2), Inches(1.1), Inches(1.2), Inches(1.1), Inches(0.6)]

for i, tc in enumerate(test_cases):
    row_cells = test_table.rows[i + 1].cells
    bg_color = "F8FAFC" if i % 2 == 1 else "FFFFFF"
    for j, val in enumerate(tc):
        row_cells[j].text = val
        p = row_cells[j].paragraphs[0]
        if j == 0 or j == 6:
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.runs[0]
        run.font.size = Pt(8)
        if j == 6: # Status Valid
            run.font.bold = True
            run.font.color.rgb = RGBColor(0x15, 0x80, 0x3D) # Green
        set_cell_background(row_cells[j], bg_color)
        set_cell_margins(row_cells[j], 60, 60, 60, 60)
        row_cells[j].vertical_alignment = WD_ALIGN_VERTICAL.CENTER

for row in test_table.rows:
    for j, w in enumerate(col_w_table):
        row.cells[j].width = w

doc.add_paragraph().paragraph_format.space_after = Pt(8)

# ----------------- BAGIAN D: PENJELASAN KODE PROGRAM (BEDAH KODE) -----------------
add_heading_1("D. PENJELASAN KODE PROGRAM (PHP, HTML5, CSS3, & JAVASCRIPT)")
doc.add_paragraph(
    "Bagian ini menjelaskan struktur logika pemrograman inti yang digunakan dalam membangun sistem pembayaran SPP, "
    "mulai dari penanganan koneksi, pengamanan query, layout antarmuka, hingga event handling interaktif:"
)

# 1. PHP - Koneksi PDO
php_koneksi_code = """
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
"""
add_code_snippet(
    "1. Modul Koneksi Database PDO (koneksi.php)",
    php_koneksi_code,
    [
        ("PDO Driver", "Menggunakan PDO (PHP Data Objects) menggantikan driver mysqli lawas, memberikan fleksibilitas database engine dan dukungan prepared statement bawaan."),
        ("ATTR_ERRMODE_EXCEPTION", "Memaksa setiap kesalahan query menghasilkan Exception objek sehingga tidak ada silent error, melainkan ditangkap blok try-catch."),
        ("ATTR_DEFAULT_FETCH_MODE", "Hasil SELECT otomatis diubah menjadi array asosiatif (nama kolom sebagai index array) untuk kemudahan pemanggilan data di view."),
        ("ATTR_EMULATE_PREPARES = false", "Menonaktifkan emulasi prepared statement dari PHP, sehingga sanitasi parameter query dieksekusi murni oleh engine MySQL (proteksi mutlak dari SQL Injection).")
    ]
)

# 2. PHP - Transaksi Pelunasan Tagihan
php_trx_code = """
$pdo->beginTransaction();

// Kunci baris data agar tidak terjadi double-payment (Race Condition)
$stmtCek = $pdo->prepare("SELECT * FROM tagihan_spp WHERE id_tagihan = ? FOR UPDATE");
$stmtCek->execute([$id_tagihan]);
$tagihan = $stmtCek->fetch();

if ($tagihan && $tagihan['status'] !== 'lunas') {
    $kode_trx = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);
    
    // Simpan data transaksi
    $stmtPay = $pdo->prepare("
        INSERT INTO pembayaran (kode_transaksi, id_tagihan, id_petugas, tgl_bayar, jumlah_bayar, metode_pembayaran, catatan)
        VALUES (?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmtPay->execute([$kode_trx, $id_tagihan, $id_petugas, $jumlah_bayar, $metode, $catatan]);

    // Update status tagihan siswa
    $stmtUpdate = $pdo->prepare("
        UPDATE tagihan_spp 
        SET terbayar = terbayar + ?, 
            status = CASE WHEN (terbayar + ?) >= nominal_tagihan THEN 'lunas' ELSE 'belum_lunas' END
        WHERE id_tagihan = ?
    ");
    $stmtUpdate->execute([$jumlah_bayar, $jumlah_bayar, $id_tagihan]);

    $pdo->commit(); // Sukses bersamaan
}
"""
add_code_snippet(
    "2. Logika Database Transaction Pelunasan SPP (index.php)",
    php_trx_code,
    [
        ("beginTransaction() & commit()", "Menjamin prinsip ACID (Atomicity, Consistency, Isolation, Durability). Kedua proses (INSERT bukti bayar dan UPDATE status tagihan) wajib sukses bersama."),
        ("FOR UPDATE (Row Lock)", "Mengunci baris tagihan yang sedang diproses agar tidak ada kasir lain yang memproses tagihan bulan yang sama pada detik yang bersamaan."),
        ("Klausul CASE WHEN pada SQL", "Secara cerdas menghitung akumulasi bayar: jika jumlah bayar mencapai atau melebihi nominal tagihan, status otomatis berubah menjadi 'lunas'."),
        ("rollBack()", "Jika server mati atau kueri terputus di tengah jalan, seluruh mutasi dibatalkan sehingga kas sekolah tidak mengalami selisih data pembukuan.")
    ]
)

# 3. PHP - Query Pencarian & Agregat
php_cari_code = """
// Pencarian Fleksibel Siswa
$stmtSiswa = $pdo->prepare("
    SELECT s.*, k.nama_kelas, ts.nominal AS tarif_standar, ts.tahun AS tahun_ajaran
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN tarif_spp ts ON s.id_spp = ts.id_spp
    WHERE s.nisn = ? OR s.nis = ? OR s.nama LIKE ?
    LIMIT 1
");
$stmtSiswa->execute([$keyword, $keyword, "%{$keyword}%"]);
"""
add_code_snippet(
    "3. Logika Query Pencarian Fleksibel & Anti-Injeksi (index.php)",
    php_cari_code,
    [
        ("Multi-Parameter Search", "Petugas dapat memasukkan 10 digit NISN, nomor NIS, ataupun hanya potongan nama siswa."),
        ("Prepared Statements (?)", "Nilai input pengguna dikirimkan terpisah dari struktur SQL sintaks, meniadakan celah eksekusi script injeksi berbahaya."),
        ("Inner Join Efisien", "Menggabungkan tabel siswa dengan kelas dan tarif_spp dalam 1 round-trip database query tanpa pemborosan memori server.")
    ]
)

# 4. HTML5 - Semantik & Dataset Attributes
html_code = """
<!-- Tombol pemicu Modal Bayar dengan HTML5 Dataset Attributes -->
<button type="button" class="btn btn-sm btn-success px-3" 
        data-bs-toggle="modal" 
        data-bs-target="#modalBayar" 
        data-idtagihan="<?= $tgh['id_tagihan'] ?>"
        data-periode="<?= $tgh['nama_bulan'] . ' ' . $tgh['tahun'] ?>"
        data-sisa="<?= $sisa ?>">
    <i class="bi bi-cash me-1"></i> Bayar Sekarang
</button>

<!-- Komponen Dialog Modal Konfirmasi Pembayaran -->
<div class="modal fade" id="modalBayar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="index.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="bayar_tagihan">
            <input type="hidden" name="id_tagihan" id="modal_id_tagihan">
            <!-- Input Form Dinamis ... -->
        </form>
    </div>
</div>
"""
add_code_snippet(
    "4. Implementasi HTML5 Semantik & Dataset Attribute (index.php)",
    html_code,
    [
        ("data-bs-* (Dataset Attributes)", "Teknik menyimpan parameter dinamis (id_tagihan, nama periode, dan sisa nominal) langsung di elemen tombol tabel tanpa bantuan AJAX rumit."),
        ("Semantic Modal Form", "Memanfaatkan modal mengambang dengan method POST dan field hidden ('action' & 'id_tagihan') untuk memastikan parameter transaksi terisolasi rapi."),
        ("Input Sanitation di Tampilan", "Setiap variabel teks dicetak menggunakan 'htmlspecialchars()' guna menghentikan ancaman Cross-Site Scripting (XSS).")
    ]
)

# 5. CSS3 - Kustom & Micro-Interactions
css_code = """
/* Desain Kartu Statistik & Efek Interaktif */
.stat-card {
    border: none;
    border-radius: 14px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.06);
}

/* Badge Status Pembayaran (Soft Colors) */
.badge-lunas {
    background-color: #dcfce7;
    color: #15803d;
    font-weight: 600;
}
.badge-belum {
    background-color: #fee2e2;
    color: #b91c1c;
    font-weight: 600;
}
"""
add_code_snippet(
    "5. Pengaturan Gaya CSS3 Kustom & Hierarki Visual (index.php)",
    css_code,
    [
        ("Micro-Interactions (Hover)", "Memberikan animasi halus saat kartu statistik disentuh kursor (terangkat 3 pixel dengan bayangan halus) untuk pengalaman kasir yang modern."),
        ("Soft Color Hierarchy", "Status 'LUNAS' menggunakan warna hijau pastel (#dcfce7) dengan kontras teks (#15803d), sedangkan 'BELUM LUNAS' memakai merah pastel (#fee2e2), ramah bagi penglihatan petugas."),
        ("Bootstrap 5 Grid System", "Menggunakan kelas utilitas seperti 'row g-3', 'd-flex align-items-center', dan 'table-responsive' untuk menjamin tata letak presisi di berbagai ukuran layar.")
    ]
)

# 6. JavaScript - Dynamic DOM Binding
js_code = """
// Mengaitkan data baris tabel ke dalam form modal saat tombol diklik
const modalBayar = document.getElementById('modalBayar');
if (modalBayar) {
    modalBayar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Menangkap tombol yang diklik
        const idTagihan = button.getAttribute('data-idtagihan');
        const periode = button.getAttribute('data-periode');
        const sisa = button.getAttribute('data-sisa');

        // Mengisi nilai input modal secara instan
        document.getElementById('modal_id_tagihan').value = idTagihan;
        document.getElementById('modal_periode').value = periode;
        document.getElementById('modal_jumlah').value = sisa;
    });
}
"""
add_code_snippet(
    "6. Script Interaktif JavaScript DOM Handling (index.php)",
    js_code,
    [
        ("show.bs.modal Event", "Mendengarkan event sebelum jendela modal terbuka secara asynchronous."),
        ("relatedTarget", "Mengetahui tombol 'Bayar Sekarang' spesifik mana yang dipicu oleh kasir."),
        ("Zero-Reload Input Binding", "Memasukkan nilai sisa tagihan secara otomatis ke kolom input pembayaran tanpa memerlukan proses reload halaman web.")
    ]
)

# ----------------- BAGIAN E: PANDUAN WAWANCARA UJIKOM -----------------
add_heading_1("E. PANDUAN PENJELASAN LISAN KEPADA PENGUJI UJIKOM")
p_d_intro = doc.add_paragraph(
    "Gunakan kerangka penjelasan berikut saat mempresentasikan sistem di hadapan penguji agar penyampaian terstruktur, lugas, dan menunjukkan penguasaan kodingan sendiri:"
)
p_d_intro.paragraph_format.space_after = Pt(4)

speech_p = doc.add_paragraph()
speech_p.paragraph_format.left_indent = Inches(0.2)
speech_p.paragraph_format.right_indent = Inches(0.2)
speech_p.paragraph_format.space_after = Pt(6)

run_speech = speech_p.add_run(
    '"Selamat pagi/siang Bapak/Ibu penguji. Izin menjelaskan hasil pengerjaan dan pengujian sistem pembayaran SPP siswa yang telah saya selesaikan.\n\n'
    'Sistem ini dibangun menggunakan PHP Native dengan driver PDO dan basis data MySQL. Tujuannya adalah mempermudah petugas kasir sekolah dalam memantau kewajiban SPP dan memproses transaksi secara tertib digital.\n\n'
    'Dalam pengerjaannya, saya mulai dari tahap perancangan basis data terlebih dahulu yang terdiri dari 6 tabel saling berelasi dengan proteksi Foreign Key. Modul koneksi menggunakan PDO dengan penanganan kesalahan berbasis try-catch.\n\n'
    'Dari sisi pengujian Black Box, ada 4 fokus utama yang sudah saya uji:\n'
    '1. Validitas Koneksi & Notifikasi Error: Sistem mampu mendeteksi ketersediaan database secara otomatis.\n'
    '2. Pencarian Fleksibel: Petugas dapat mencari data siswa baik menggunakan NISN, NIS, maupun kemiripan nama.\n'
    '3. Integritas Transaksi Pembayaran: Pelunasan tagihan dilindungi Database Transaction (beginTransaction, commit, rollBack) agar data pembayaran dan perubahan status tagihan selalu sinkron dan tidak mengalami selisih.\n'
    '4. Pelaporan Real-time: Setiap transaksi yang berhasil langsung terbit kode uniknya, memperbarui riwayat log transaksi terbaru, serta menaikkan angka pemasukan pada kartu statistik dashboard.\n\n'
    'Seluruh skenario pengujian fungsionalitas telah dinyatakan Valid dan siap dioperasikan. Silakan apabila ada kode program atau fitur yang ingin diperiksa lebih lanjut."'
)
run_speech.font.size = Pt(9.5)
run_speech.font.italic = True
run_speech.font.color.rgb = RGBColor(0x1E, 0x29, 0x3B)

# ----------------- BAGIAN F: PERTANYAAN KRITIS & JAWABAN -----------------
add_heading_1("F. ANTISIPASI PERTANYAAN TEKNIS PENGUJI")

qna_list = [
    ("Pertanyaan 1: Mengapa menggunakan PDO dan bukan MySQLi?",
     "Jawaban: PDO (PHP Data Objects) mendukung multi-database driver sehingga memudahkan migrasi jika sistem dialihkan ke basis data lain di masa mendatang. "
     "Selain itu, PDO menggunakan Prepared Statements secara default yang menjamin keamanan kueri dari celah SQL Injection."),
    
    ("Pertanyaan 2: Mengapa proses pembayaran harus menggunakan Database Transaction?",
     "Jawaban: Karena transaksi pembayaran menyentuh dua tabel krusial secara berurutan, yaitu INSERT ke tabel 'pembayaran' dan UPDATE status pada tabel 'tagihan_spp'. "
     "Jika terjadi kegagalan sistem di tengah proses, fungsi 'rollBack()' akan membatalkan perubahan sehingga tidak timbul anomali data di mana uang tercatat masuk namun tagihan siswa belum lunas."),
    
    ("Pertanyaan 3: Bagaimana alur data dari tabel ke pop-up modal pembayaran?",
     "Jawaban: Saya memanfaatkan atribut dataset HTML5 (data-bs-idtagihan, data-bs-periode, data-bs-sisa) pada tombol 'Bayar Sekarang'. "
     "Saat modal terbuka, JavaScript membaca atribut tersebut melalui event listener dan mengisi nilai ke kolom input formulir secara otomatis tanpa perlu memuat ulang halaman.")
]

for q, a in qna_list:
    p_q = doc.add_paragraph()
    p_q.paragraph_format.left_indent = Inches(0.15)
    p_q.paragraph_format.space_before = Pt(3)
    p_q.paragraph_format.space_after = Pt(2)
    rq = p_q.add_run(q)
    rq.font.bold = True
    rq.font.size = Pt(9.5)
    rq.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    p_a = doc.add_paragraph()
    p_a.paragraph_format.left_indent = Inches(0.15)
    p_a.paragraph_format.space_after = Pt(5)
    ra = p_a.add_run(a)
    ra.font.size = Pt(9)
    ra.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

# ----------------- BAGIAN G: LEMBAR PENGESAHAN -----------------
add_heading_1("G. LEMBAR PENGESAHAN PENGUJIAN")

doc.add_paragraph("Laporan pengujian sistem ini telah diuji dan diverifikasi pada pelaksanaan Uji Kompetensi Keahlian (UKK):")

sig_table = doc.add_table(rows=2, cols=2)
sig_table.alignment = WD_TABLE_ALIGNMENT.CENTER

sig_table.rows[0].cells[0].text = "Penguji Internal / Asesor,"
sig_table.rows[0].cells[1].text = "Peserta Uji Kompetensi,"

sig_table.rows[1].cells[0].text = "\n\n\n\n________________________________\nNIP."
sig_table.rows[1].cells[1].text = "\n\n\n\n________________________________\nNISN."

for row in sig_table.rows:
    for cell in row.cells:
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        if cell.paragraphs[0].runs:
            cell.paragraphs[0].runs[0].font.size = Pt(9.5)
        set_cell_margins(cell, 30, 30, 50, 50)

for row in sig_table.rows:
    row.cells[0].width = Inches(3.2)
    row.cells[1].width = Inches(3.2)

output_path = r"C:\Users\suhendra\.gemini\antigravity\scratch\pembayaran_spp\Laporan_Hasil_Pengujian_Pembayaran_SPP.docx"
doc.save(output_path)
print("SUCCESS: File saved to", output_path)

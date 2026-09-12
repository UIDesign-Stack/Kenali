<?php

namespace Database\Seeders;

use App\Models\SubCriteria;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // ===== MINAT (RIASEC) =====
            'realistic' => [
                'Saya lebih suka bekerja dengan alat, mesin, atau benda fisik dibanding duduk di depan komputer seharian.',
                'Saya senang memperbaiki barang yang rusak di rumah, seperti kabel, keran, atau perabot.',
                'Saya lebih memilih kegiatan di luar ruangan dibanding di dalam kantor.',
                'Saya tertarik memahami cara kerja suatu mesin atau perangkat secara langsung.',
            ],
            'investigative' => [
                'Saya senang menganalisis data untuk menemukan pola atau penjelasan tertentu.',
                'Saya suka mencari tahu "kenapa" sesuatu terjadi, bukan hanya menerima begitu saja.',
                'Saya menikmati memecahkan teka-teki atau masalah yang rumit.',
                'Saya tertarik membaca artikel atau riset ilmiah tentang topik yang belum saya pahami.',
            ],
            'artistic' => [
                'Saya senang mengekspresikan ide lewat gambar, tulisan, musik, atau bentuk seni lainnya.',
                'Saya lebih suka pekerjaan yang memberi kebebasan berkreasi dibanding yang penuh aturan baku.',
                'Saya sering memperhatikan detail estetika seperti warna, bentuk, atau komposisi.',
                'Saya menikmati membuat sesuatu yang orisinal, bukan sekadar meniru yang sudah ada.',
            ],
            'social' => [
                'Saya senang membantu orang lain menyelesaikan masalah pribadi atau pekerjaan mereka.',
                'Saya nyaman berbicara di depan orang banyak atau memimpin diskusi kelompok.',
                'Saya lebih suka bekerja dalam tim dibanding bekerja sendirian.',
                'Saya tertarik pada pekerjaan yang melibatkan mengajar, melatih, atau membimbing orang lain.',
            ],
            'enterprising' => [
                'Saya senang mengambil inisiatif untuk memulai sesuatu yang baru.',
                'Saya nyaman meyakinkan orang lain untuk setuju dengan ide atau rencana saya.',
                'Saya tertarik pada peluang bisnis atau cara menghasilkan keuntungan.',
                'Saya berani mengambil risiko demi mencapai target yang lebih besar.',
            ],
            'conventional' => [
                'Saya senang bekerja dengan data, angka, atau dokumen yang terstruktur rapi.',
                'Saya lebih nyaman mengikuti prosedur yang jelas dibanding mencoba cara baru yang belum pasti.',
                'Saya teliti dalam memeriksa detail kecil seperti ejaan, format, atau perhitungan.',
                'Saya suka menyusun jadwal, arsip, atau catatan secara sistematis.',
            ],

            // ===== BAKAT (DAT) =====
            'verbal' => [
                'Saya mudah memahami bacaan yang menggunakan kosakata rumit.',
                'Saya bisa menjelaskan suatu ide dengan kalimat yang runtut dan mudah dipahami orang lain.',
                'Saya cepat menangkap makna tersirat dalam sebuah kalimat atau paragraf.',
                'Saya jarang kesulitan menemukan kata yang tepat saat menulis atau berbicara.',
            ],
            'numerik' => [
                'Saya bisa berhitung dengan cepat tanpa terlalu bergantung pada kalkulator.',
                'Saya mudah memahami soal yang melibatkan angka, persentase, atau statistik.',
                'Saya jarang membuat kesalahan saat mengerjakan perhitungan matematis.',
                'Saya senang menyelesaikan soal logika yang melibatkan angka.',
            ],
            'spasial' => [
                'Saya mudah membayangkan bentuk suatu objek dari berbagai sudut pandang.',
                'Saya bisa membaca peta atau denah ruangan dengan mudah.',
                'Saya mudah menyusun puzzle atau memahami pola bentuk yang rumit.',
                'Saya bisa membayangkan bagaimana suatu benda akan terlihat setelah diputar atau dilipat.',
            ],
            'penalaran_abstrak' => [
                'Saya mudah menemukan pola dalam serangkaian gambar atau simbol.',
                'Saya bisa menebak kelanjutan suatu urutan logis dengan cukup akurat.',
                'Saya senang menyelesaikan teka-teki logika yang tidak melibatkan angka atau kata.',
                'Saya bisa berpikir sistematis meski informasi yang diberikan terbatas.',
            ],
            'klerikal' => [
                'Saya cepat menemukan kesalahan kecil dalam sebuah dokumen atau data.',
                'Saya bisa menyelesaikan tugas administratif dengan teliti dan cepat.',
                'Saya jarang melewatkan detail saat memeriksa data dalam jumlah banyak.',
                'Saya nyaman mengerjakan pekerjaan yang berulang namun membutuhkan ketelitian tinggi.',
            ],
            'mekanikal' => [
                'Saya memahami cara kerja alat sederhana seperti katrol, roda gigi, atau tuas.',
                'Saya tertarik memahami prinsip fisika di balik cara kerja suatu mesin.',
                'Saya cukup mudah memahami diagram teknis atau skema alat.',
                'Saya senang mengutak-atik peralatan untuk memahami cara kerjanya.',
            ],

            // ===== KEPRIBADIAN (Big Five) =====
            'openness' => [
                'Saya senang mencoba hal-hal baru yang belum pernah saya lakukan sebelumnya.',
                'Saya tertarik pada ide-ide abstrak atau cara berpikir yang tidak konvensional.',
                'Saya senang mengeksplorasi topik di luar bidang yang saya kuasai.',
                'Saya nyaman menghadapi situasi yang tidak terduga atau berubah-ubah.',
            ],
            'conscientiousness' => [
                'Saya menyelesaikan tugas sesuai jadwal yang sudah saya tentukan.',
                'Saya orang yang terorganisir dalam mengatur pekerjaan sehari-hari.',
                'Saya tetap disiplin mengerjakan sesuatu meski tidak ada yang mengawasi.',
                'Saya memperhatikan detail agar hasil pekerjaan saya rapi dan akurat.',
            ],
            'extraversion' => [
                'Saya merasa berenergi setelah menghabiskan waktu bersama banyak orang.',
                'Saya mudah memulai percakapan dengan orang yang baru saya kenal.',
                'Saya lebih suka suasana ramai dibanding suasana yang sepi dan tenang.',
                'Saya nyaman menjadi pusat perhatian dalam suatu kelompok.',
            ],
            'agreeableness' => [
                'Saya berusaha menjaga keharmonisan dalam kelompok meski ada perbedaan pendapat.',
                'Saya mudah mempercayai niat baik orang lain.',
                'Saya senang membantu orang lain meski tidak diminta.',
                'Saya cenderung mengalah demi menghindari konflik yang tidak perlu.',
            ],
            'stabilitas_emosi' => [
                'Saya tetap tenang saat menghadapi situasi yang penuh tekanan.',
                'Saya jarang merasa cemas berlebihan terhadap hal-hal kecil.',
                'Saya bisa cepat pulih setelah mengalami kekecewaan atau kegagalan.',
                'Saya jarang terbawa emosi saat menghadapi kritik dari orang lain.',
            ],
        ];

        foreach ($questions as $code => $list) {
            $subCriteria = SubCriteria::where('code', $code)->first();

            if (! $subCriteria) {
                $this->command->warn("Sub-kriteria '{$code}' belum ada, lewati.");
                continue;
            }

            foreach ($list as $index => $text) {
                $subCriteria->questions()->firstOrCreate(
                    ['question_text' => $text],
                    ['order' => $index + 1, 'is_active' => true]
                );
            }
        }

        $this->command->info('Contoh soal berhasil ditambahkan untuk semua sub-kriteria.');
    }
}
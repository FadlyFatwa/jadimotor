@section('scripts')
<script>
    $(document).ready(function () {
        function setPilihState($row, selected) {
            var itemId = $row.data("item-id");
            var $btn = $row.find(".btn-pilih-supplier");
            var $checkbox = $('input.saw-checkbox[value="' + itemId + '"]');

            $checkbox.prop("checked", selected);
            $row.toggleClass("table-success", selected);
            if (selected) {
                $btn.removeClass("btn-outline-secondary")
                    .addClass("btn-success")
                    .html('<i class="fas fa-check mr-1"></i>Dipilih');
            } else {
                $btn.removeClass("btn-success")
                    .addClass("btn-outline-secondary")
                    .text("Pilih");
            }
        }

        // =========================================================================
        // Pilih manual per baris supplier (aktif setelah rekomendasi kelompok dihitung)
        // Hanya 1 supplier boleh terpilih per KELOMPOK/grade group (radio) — variasi/
        // merk lain dalam kelompok yang sama adalah alternatif yang saling bersaing,
        // bukan kebutuhan terpisah. Kalau yang sedang terpilih adalah hasil
        // rekomendasi, konfirmasi dulu sebelum diganti.
        // =========================================================================

        $(document).on("click", ".btn-pilih-supplier", function () {
            var $btn = $(this);
            var $row = $btn.closest("tr");
            var itemId = $btn.data("item-id");
            var panelKey = $row.data("panel-key");
            var isNowSelected = $btn.hasClass("btn-success");

            // Klik baris yang sudah terpilih → cuma lepas pilihan itu, tidak ganti ke baris lain.
            if (isNowSelected) {
                setPilihState($row, false);
                return;
            }

            // Cek langsung berdasarkan baris rekomendasi untuk kelompok ini (bukan
            // baris yang sedang tercentang) — supaya tetap kedeteksi walau baris
            // rekomendasi itu belum/tidak tercentang saat ini.
            var $recommendedRow = $(
                'tr.saw-supplier-row[data-panel-key="' +
                    panelKey +
                    '"][data-is-recommended="1"]',
            );
            var isSwitchingFromRecommendation =
                $recommendedRow.length > 0 &&
                String($recommendedRow.data("item-id")) !== String(itemId);

            var applySelection = function () {
                // Satu supplier per kelompok — lepas semua baris lain di kelompok ini.
                $(
                    'tr.saw-supplier-row[data-panel-key="' + panelKey + '"]',
                ).each(function () {
                    setPilihState($(this), false);
                });
                setPilihState($row, true);
            };

            if (isSwitchingFromRecommendation) {
                Swal.fire({
                    icon: "warning",
                    title: "Ganti Pilihan Supplier?",
                    text: "Ini adalah hasil rekomendasi. Anda yakin ingin mengganti pilihan supplier?",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Ganti",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#adb5bd",
                    reverseButtons: true,
                }).then(function (result) {
                    if (result.isConfirmed) applySelection();
                });
            } else {
                applySelection();
            }
        });

        // =========================================================================
        // Simpan Pilihan
        // =========================================================================

        $("#btnSimpanPilihan").on("click", function () {
            var formAction = $(this).data("formaction");
            var sudahDipilihLengkap = $(this).data("sudah-dipilih-lengkap") == 1;

            if (sudahDipilihLengkap) {
                Swal.fire({
                    icon: "warning",
                    title: "Sudah Pernah Dipilih Lengkap",
                    html:
                        "Needlist ini sebelumnya sudah punya pilihan supplier untuk semua kelompok.<br>" +
                        '<small class="text-muted">Kalau lanjut, pilihan lama akan ditimpa dengan yang baru.</small>',
                    showCancelButton: true,
                    confirmButtonText: "Ya, Lanjutkan Ubah",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#adb5bd",
                    reverseButtons: true,
                }).then(function (result) {
                    if (result.isConfirmed) validateAndConfirmSimpanPilihan(formAction);
                });
                return;
            }

            validateAndConfirmSimpanPilihan(formAction);
        });

        function validateAndConfirmSimpanPilihan(formAction) {
            var selectablePanelKeys = [];
            $("input.saw-checkbox:not([disabled])").each(function () {
                var pk = String($(this).data("panel-key"));
                if (selectablePanelKeys.indexOf(pk) === -1)
                    selectablePanelKeys.push(pk);
            });

            var missingLabels = [];
            selectablePanelKeys.forEach(function (pk) {
                if (
                    $(
                        'input.saw-checkbox[data-panel-key="' +
                            pk +
                            '"]:checked',
                    ).length === 0
                ) {
                    var $firstRow = $(
                        'tr.saw-supplier-row[data-panel-key="' + pk + '"]',
                    ).first();
                    var groupLabel =
                        $firstRow.data("group-label") ||
                        "Kelompok #" + pk;
                    if (missingLabels.indexOf(groupLabel) === -1)
                        missingLabels.push(groupLabel);
                }
            });

            if (missingLabels.length > 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Pilihan Belum Lengkap",
                    html:
                        "Pilih minimal <strong>1 supplier</strong> untuk setiap kelompok.<br><br>" +
                        '<small class="text-danger">Belum dipilih: ' +
                        missingLabels
                            .map(function (l) {
                                return "<strong>" + l + "</strong>";
                            })
                            .join(", ") +
                        "</small>",
                    confirmButtonColor: "#3085d6",
                });
                return;
            }

            var totalChecked = $("input.saw-checkbox:checked").length;
            Swal.fire({
                title: "Simpan Pilihan?",
                html:
                    "Pilihan untuk <strong>" +
                    totalChecked +
                    " item</strong> akan disimpan.<br>" +
                    '<small class="text-muted">Anda masih bisa mengubah pilihan sebelum terbitkan PO.</small>',
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#007bff",
                cancelButtonColor: "#adb5bd",
                confirmButtonText: "Ya, Simpan",
                cancelButtonText: "Batal",
                reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $("#formPemilihan").attr("action", formAction).submit();
            });
        }
    });

    // =========================================================================
    // Modal Detail Item
    // =========================================================================
    (function () {
        var tierColors = {
            OEM: "primary",
            Original: "success",
            Aftermarket: "warning",
            KW: "secondary",
        };

        function fmt(n) {
            if (n === null || n === undefined || n === "") return "-";
            return parseInt(n).toLocaleString("id-ID");
        }
        function fmtDate(s) {
            if (!s) return "-";
            try {
                var d = new Date(s);
                var m = [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "Mei",
                    "Jun",
                    "Jul",
                    "Ags",
                    "Sep",
                    "Okt",
                    "Nov",
                    "Des",
                ];
                return (
                    d.getDate() + " " + m[d.getMonth()] + " " + d.getFullYear()
                );
            } catch (e) {
                return s;
            }
        }
        function badge(cls, txt) {
            return '<span class="badge badge-' + cls + '">' + txt + "</span>";
        }
        function trow(label, val) {
            return (
                "<tr>" +
                '<td class="text-muted align-middle py-1" style="width:42%;white-space:nowrap;font-size:.82rem;">' +
                label +
                "</td>" +
                '<td class="align-middle py-1">' +
                val +
                "</td>" +
                "</tr>"
            );
        }
        function sectionHead(icon, title, extra) {
            return (
                '<p class="mb-1 mt-3 font-weight-bold text-secondary border-bottom pb-1" style="font-size:.82rem;">' +
                '<i class="' +
                icon +
                ' mr-1"></i>' +
                title +
                (extra ? " " + extra : "") +
                "</p>"
            );
        }

        $(document).on("click", ".btn-detail-variasi", function () {
            var d = $(this).data("detail");
            if (!d) return;

            var tierBadge = d.tier
                ? '<span class="badge badge-' +
                  (tierColors[d.tier] || "secondary") +
                  ' ml-1">' +
                  d.tier +
                  "</span>"
                : "";
            var supBadge =
                d.supplier_count > 1
                    ? badge(
                          "info",
                          '<i class="fas fa-users mr-1"></i>Multi Supplier (' +
                              d.supplier_count +
                              ")",
                      )
                    : d.supplier_count === 1
                      ? badge(
                            "secondary",
                            '<i class="fas fa-user mr-1"></i>Supplier Tunggal',
                        )
                      : badge("light border text-muted", "Belum ada");
            var stockHtml =
                d.stock <= 0
                    ? badge("danger", "Habis")
                    : d.stock <= 5
                      ? badge("warning", d.stock + " unit")
                      : badge("light border", d.stock + " unit");

            // ── Modal title ──────────────────────────────────────────────────
            $("#modalDetailVariasiLabel").html(
                '<i class="fas fa-info-circle mr-1 text-secondary"></i>' +
                    "<strong>" +
                    d.nama_variasi +
                    "</strong> " +
                    tierBadge +
                    " " +
                    supBadge,
            );

            // ── Left column ──────────────────────────────────────────────────
            var left = sectionHead("fas fa-box", "Informasi Barang");
            left += '<table class="table table-xs table-sm mb-0"><tbody>';
            left += trow(
                "Master Barang",
                "<strong>" + d.master_nama + "</strong>",
            );
            left += trow(
                "Kode Barang",
                "<code>" + (d.master_kode || "-") + "</code>",
            );
            left += trow("Kategori", d.master_kategori || "-");
            left += trow(
                "Deskripsi",
                d.master_desc
                    ? "<small>" + d.master_desc + "</small>"
                    : '<span class="text-muted">-</span>',
            );
            left += "</tbody></table>";

            left += sectionHead("fas fa-tag", "Detail Variasi");
            left += '<table class="table table-xs table-sm mb-0"><tbody>';
            left += trow(
                "Nama Variasi",
                "<strong>" + d.nama_variasi + "</strong>",
            );
            left += trow(
                "Barcode",
                "<code>" +
                    (d.barcode && d.barcode !== "-" ? d.barcode : "-") +
                    "</code>",
            );
            left += trow(
                "No. Part",
                "<code>" +
                    (d.part_number && d.part_number !== "-"
                        ? d.part_number
                        : "-") +
                    "</code>",
            );
            left += trow("Grade / Tier", tierBadge || "-");
            left += trow(
                "Harga Jual",
                d.harga_jual > 0
                    ? "Rp <strong>" + fmt(d.harga_jual) + "</strong>"
                    : '<span class="text-muted">-</span>',
            );
            left += trow("Stok Saat Ini", stockHtml);
            left += "</tbody></table>";

            left += sectionHead("fas fa-clipboard-list", "Data Order");
            left += '<table class="table table-xs table-sm mb-0"><tbody>';
            left += trow(
                "Qty Order",
                "<strong>" + (d.qty || "-") + "</strong> pcs",
            );
            left += "</tbody></table>";

            // ── Right column ─────────────────────────────────────────────────
            var right = sectionHead("fas fa-car", "Kompatibilitas Kendaraan");
            if (d.kendaraan && d.kendaraan.length > 0) {
                right +=
                    '<ul class="list-unstyled mb-0" style="font-size:.82rem;">';
                d.kendaraan.forEach(function (k) {
                    var tahun = k.tahun_mulai
                        ? " (" +
                          String(k.tahun_mulai).slice(-2) +
                          "–" +
                          (k.tahun_akhir
                              ? String(k.tahun_akhir).slice(-2)
                              : "skrg") +
                          ")"
                        : "";
                    right +=
                        '<li class="mb-1">' +
                        '<i class="fas fa-car fa-xs text-muted mr-1"></i>' +
                        "<strong>" +
                        k.nama +
                        "</strong> " +
                        '<code style="font-size:.78rem;">' +
                        k.generasi +
                        "</code>" +
                        tahun +
                        "</li>";
                });
                right += "</ul>";
            } else {
                right +=
                    '<small class="text-muted">Semua / Tidak spesifik</small>';
            }

            right += sectionHead(
                "fas fa-truck",
                "Konfirmasi Harga per Supplier",
                supBadge,
            );
            right +=
                '<table class="table table-xs table-sm table-bordered mb-0" style="font-size:.8rem;">' +
                '<thead class="thead-light"><tr>' +
                "<th>Supplier</th><th>Kode</th>" +
                '<th class="text-right">Harga Konfirmasi</th>' +
                '<th class="text-center">Qty</th>' +
                '<th class="text-center">Status</th>' +
                "</tr></thead><tbody>";

            if (d.suppliers && d.suppliers.length > 0) {
                d.suppliers.forEach(function (s) {
                    var harga = s.harga
                        ? "<strong>Rp " + fmt(s.harga) + "</strong>"
                        : '<span class="text-danger">Belum diisi</span>';
                    var rekBadge = s.is_recommended
                        ? ' <span class="badge badge-warning" style="font-size:.6rem;">Direkomendasikan</span>'
                        : "";
                    right +=
                        "<tr>" +
                        "<td>" +
                        s.nama +
                        rekBadge +
                        "</td>" +
                        "<td><code>" +
                        (s.kode || "-") +
                        "</code></td>" +
                        '<td class="text-right">' +
                        harga +
                        "</td>" +
                        '<td class="text-center">' +
                        (d.qty || "-") +
                        "</td>" +
                        "<td>" +
                        fmtDate(s.estimasi) +
                        "</td>" +
                        '<td class="text-center">' +
                        (s.harga
                            ? badge("success", "✓")
                            : badge("secondary", "—")) +
                        "</td>" +
                        "</tr>";
                });
            } else {
                right +=
                    '<tr><td colspan="6" class="text-center text-muted">Tidak ada data supplier</td></tr>';
            }
            right += "</tbody></table>";

            var body =
                '<div class="row">' +
                '<div class="col-md-5">' +
                left +
                "</div>" +
                '<div class="col-md-7">' +
                right +
                "</div>" +
                "</div>";

            $("#modalDetailVariasiBody").html(body);
            $("#modalDetailVariasi").modal("show");
        });
    })();
</script>
@endsection

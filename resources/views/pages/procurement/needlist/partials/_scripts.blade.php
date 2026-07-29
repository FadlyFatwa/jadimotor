@section('scripts')
<script>
    $(document).ready(function () {

        // ── Restore tab dari sessionStorage / URL hash ────────────────────
        var savedTab = sessionStorage.getItem('activeNeedlistTab_{{ $needlist->id }}');
        var hashTab  = window.location.hash;
        if (hashTab) {
            $('a[data-toggle="tab"][href="' + hashTab + '"]').tab('show');
        } else if (savedTab) {
            $('a[data-toggle="tab"][href="' + savedTab + '"]').tab('show');
        }
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            sessionStorage.setItem('activeNeedlistTab_{{ $needlist->id }}', $(e.target).attr('href'));
        });

        // =========================================================================
        // TAB INQUIRY — Modal Konfirmasi & Preview Harga
        // =========================================================================

        $(document).on('click', '.open-fill-modal', function () {
            var id = $(this).data('id');
            $('#fillInquiryBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
            $('#fillInquiryModal').modal('show');
            $.get('/inquiry/' + id + '/fill-modal', function (html) {
                $('#fillInquiryBody').html(html);
                $('#fillInquiryForm').attr('action', '/inquiry/' + id + '/store-response');
            });
        });

        $(document).on('click', '.open-preview-modal', function () {
            var id = $(this).data('id');
            $('#previewInquiryBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
            $('#previewInquiryModal').modal('show');
            $.get('/inquiry/' + id + '/preview-modal', function (html) {
                $('#previewInquiryBody').html(html);
            });
        });

        // =========================================================================
        // TAB NEEDLIST — Toggle Referensi & Accordion Chevron
        // =========================================================================

        $(document).on('click', '.btn-toggle-ref', function () {
            var $btn   = $(this);
            var itemId = $btn.data('item-id');
            var isRef  = parseInt($btn.data('is-ref'));
            Swal.fire({
                title: isRef ? 'Aktifkan Item?' : 'Jadikan Referensi?',
                text: isRef
                    ? 'Item akan kembali aktif dan masuk ke penilaian supplier.'
                    : 'Item hanya akan tampil sebagai referensi dan tidak masuk ke penilaian.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: isRef ? '#28a745' : '#6c757d',
                cancelButtonColor: '#adb5bd',
                confirmButtonText: isRef ? 'Ya, Aktifkan' : 'Ya, Jadikan Referensi',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $btn.prop('disabled', true);
                $.post('{{ route("needlist.item.toggleReference", ["itemId" => "__ID__"]) }}'.replace('__ID__', itemId), {
                    _token: '{{ csrf_token() }}'
                })
                .done(function (res) {
                    if (!res.success) { toastr.error('Gagal mengubah status.'); return; }
                    location.reload();
                })
                .fail(function () { toastr.error('Gagal. Coba lagi.'); $btn.prop('disabled', false); });
            });
        });

        $(document).on('show.bs.collapse', '[id^="nl-mb-"]', function () {
            $('[data-target="#' + this.id + '"]').find('.fa-chevron-down').css('transform', 'rotate(180deg)');
        });
        $(document).on('hide.bs.collapse', '[id^="nl-mb-"]', function () {
            $('[data-target="#' + this.id + '"]').find('.fa-chevron-down').css('transform', 'rotate(0deg)');
        });

        // =========================================================================
        // TAB PO — Terbitkan Purchase Order
        // =========================================================================

        $('#btnBuatPo').on('click', function () {
            Swal.fire({
                title: 'Terbitkan Purchase Order?',
                html: 'PO akan dibuat dari pilihan supplier yang tersimpan.<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan setelah PO diterbitkan.</small>',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#28a745', cancelButtonColor: '#adb5bd',
                confirmButtonText: '<i class="fas fa-file-invoice mr-1"></i> Ya, Terbitkan PO',
                cancelButtonText: 'Batal', reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $('#formBuatPo').submit();
            });
        });

    });
</script>
@endsection

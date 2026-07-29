<!-- Modal Detail Needlist -->
<div class="modal fade" id="modalDetailNeedlist" tabindex="-1" role="dialog" aria-labelledby="modalNeedlistLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">📋 Detail Needlist</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-3">Kode Needlist</dt>
          <dd class="col-sm-9" id="modal_kode_needlist">-</dd>

          <dt class="col-sm-3">Tanggal</dt>
          <dd class="col-sm-9" id="modal_tanggal">-</dd>

          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9" id="modal_status">-</dd>

          <dt class="col-sm-3">Catatan Supervisor</dt>
          <dd class="col-sm-9" id="modal_catatan">-</dd>
        </dl>

        <hr>

        <h6>📦 Daftar Barang</h6>
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Master</th>
              <th>Variasi</th>
              <th>Qty</th>
              <th>Stock</th>
              <th>Harga Beli</th>
              <th>Supplier</th>
            </tr>
          </thead>
          <tbody id="modal_barang_list">
            {{-- Data dari Ajax --}}
          </tbody>
        </table>

        <hr>

        <div class="text-right" id="modal_action_buttons">
          {{-- Tombol aksi akan dimasukkan via JS --}}
        </div>
      </div>
    </div>
  </div>
</div>

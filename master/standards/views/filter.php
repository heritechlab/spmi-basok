<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <div>
            <i class="fa fa-filter me-2 text-primary"></i>
            <strong>Filter Standar</strong>
        </div>

        <button
            class="btn btn-primary"
            id="btnTambahStandar">

            <i class="fa fa-plus me-2"></i>

            Tambah Standar

        </button>

    </div>

    <div class="card-body">

        <div class="row">

            <!-- Jenis -->

            <div class="col-lg-3">

                <label class="form-label">

                    Jenis Standar

                </label>

                <select
                    id="filterType"
                    class="form-select">

                    <option value="">Semua</option>

                    <?php foreach($types as $type): ?>

                        <option value="<?= $type['id']; ?>">

                            <?= htmlspecialchars($type['name']); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <!-- Kategori -->

            <div class="col-lg-3">

                <label class="form-label">

                    Kategori

                </label>

                <select
                    id="filterCategory"
                    class="form-select">

                    <option value="">Semua</option>

                    <?php foreach($categories as $category): ?>

                    <option value="<?= $category['id']; ?>">

                    <?= htmlspecialchars($category['name']); ?>

                    </option>

                    <?php endforeach; ?>

                    </select>

            </div>

            <!-- Nama Standar -->

            <div class="col-lg-3">

                <label class="form-label">

                    Standar

                </label>

                <select
                    id="filterStandard"
                    class="form-select">

                    <option value="">Semua</option>

                    <?php foreach($standards as $standard): ?>

                    <option value="<?= $standard['id']; ?>">

                    <?= htmlspecialchars($standard['name']); ?>

                    </option>

                    <?php endforeach; ?>

                    </select>

            </div>

            <!-- Search -->

            <div class="col-lg-3">

                <label class="form-label">

                    Cari

                </label>

                <input

                    id="searchStandard"

                    class="form-control"

                    placeholder="Cari standar...">

            </div>

        </div>

    </div>

</div>
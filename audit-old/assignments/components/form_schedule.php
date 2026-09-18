<!-- ==========================================================
JADWAL AUDIT
========================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">

            <i class="bi bi-calendar-event text-primary me-2"></i>

            Jadwal Audit

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <!-- Tanggal Penugasan -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">
                    Tanggal Penugasan
                </label>

                <input
                    type="date"
                    name="assignment_date"
                    class="form-control"
                    value="<?= date('Y-m-d') ?>">

            </div>

            <!-- Tanggal Audit -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">
                    Tanggal Audit
                </label>

                <input
                    type="date"
                    name="audit_date"
                    class="form-control">

            </div>

            <!-- Metode Audit -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">
                    Metode Audit
                </label>

                <select
                    name="audit_method"
                    class="form-select">

                    <option value="Onsite">Onsite</option>
                    <option value="Online">Online</option>
                    <option value="Hybrid">Hybrid</option>

                </select>

            </div>

            <!-- Audit Dimulai -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Audit Dimulai
                </label>

                <input
                    type="datetime-local"
                    name="audit_start"
                    class="form-control">

            </div>

            <!-- Audit Selesai -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Audit Selesai
                </label>

                <input
                    type="datetime-local"
                    name="audit_finish"
                    class="form-control">

            </div>

            <!-- Lokasi -->

            <div class="col-md-12">

                <label class="form-label fw-semibold">
                    Lokasi Audit
                </label>

                <input
                    type="text"
                    name="audit_location"
                    class="form-control"
                    placeholder="Contoh : STIKES Harapan Ibu Jambi">

            </div>

        </div>

    </div>

</div>
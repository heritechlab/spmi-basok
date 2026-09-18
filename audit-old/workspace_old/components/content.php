<!-- ===========================================================
CONTENT WORKSPACE PREMIUM
=========================================================== -->

<div class="row g-3">

    <!-- =======================================================
    INFORMASI INDIKATOR
    ======================================================== -->

    <div class="col-12">

        <div class="card workspace-card">

            <div class="workspace-header">

                <i class="bi bi-list-check me-2"></i>

                Informasi Indikator

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">

                        <small class="text-muted">Kode</small>

                        <h5 class="fw-bold">

                            STD-01.01

                        </h5>

                    </div>

                    <div class="col-md-3">

                        <small class="text-muted">

                            Standar

                        </small>

                        <h6>

                            Visi Misi

                        </h6>

                    </div>

                    <div class="col-md-2">

                        <small class="text-muted">

                            Bobot

                        </small>

                        <h6>

                            10

                        </h6>

                    </div>

                    <div class="col-md-5">

                        <small class="text-muted">

                            Deskripsi Indikator

                        </small>

                        <p class="mb-0">

                            Dokumen VMTS tersedia dan telah disahkan oleh Senat Perguruan Tinggi.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>





    <!-- =======================================================
    STATUS AUDIT
    ======================================================== -->

    <div class="col-12">

        <div class="card workspace-card">

            <div class="workspace-header">

                <i class="bi bi-check2-square me-2"></i>

                Status Audit

            </div>

            <div class="card-body">

                <input

                    type="hidden"

                    id="audit_status"

                    name="audit_status"

                    value="">

                <input
                    type="hidden"
                    
                    id="audit_title"
                    
                    name="audit_title">


                <div class="row g-3">

                        <div class="col-md-6">

                            <div class="status-card"
                                data-status="0"
                                data-title="Tidak Terpenuhi">

                                <i class="bi bi-x-circle-fill display-4 text-danger"></i>

                                <h5>Tidak Terpenuhi</h5>

                                <small>Standar belum dipenuhi</small>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="status-card"
                                data-status="1"
                                data-title="Memenuhi Sebagian">

                                <i class="bi bi-exclamation-circle-fill display-4 text-warning"></i>

                                <h5>Memenuhi Sebagian</h5>

                                <small>Masih terdapat gap</small>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="status-card"
                                data-status="2"
                                data-title="Memenuhi">

                                <i class="bi bi-check-circle-fill display-4 text-success"></i>

                                <h5>Memenuhi</h5>

                                <small>Sesuai indikator</small>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="status-card"
                                data-status="3"
                                data-title="Melampaui">

                                <i class="bi bi-award-fill display-4 text-primary"></i>

                                <h5>Melampaui</h5>

                                <small>Best Practice</small>

                            </div>

                        </div>

                    </div>

            </div>

        </div>

    </div>





    <!-- =======================================================
    EVIDEN
    ======================================================== -->

    <div class="col-12">

        <div class="card workspace-card">

            <div class="workspace-header">

                <i class="bi bi-cloud-upload me-2"></i>

                Upload Eviden

            </div>

            <div class="card-body">

                <div class="upload-box">

                    <i class="bi bi-cloud-arrow-up display-3 text-primary"></i>

                    <h5 class="mt-3">

                        Drag & Drop File

                    </h5>

                    <p class="text-muted">

                        atau klik tombol di bawah

                    </p>

                    <input

                        type="file"

                        class="form-control">

                    <small class="text-muted">

                        PDF • DOC • XLS • ZIP

                    </small>

                </div>

            </div>

        </div>

    </div>





    <!-- =======================================================
    CATATAN
    ======================================================== -->

    <div class="col-12">

        <div class="card workspace-card">

            <div class="workspace-header">

                <i class="bi bi-journal-text me-2"></i>

                Catatan Auditor

            </div>

            <div class="card-body">

                <textarea

                    class="form-control"

                    rows="5"

                    placeholder="Tuliskan hasil observasi auditor..."></textarea>

            </div>

        </div>

    </div>





    <!-- =======================================================
    ANALISIS
    ======================================================== -->

    <div class="col-12">

    <div class="card workspace-card">

        <div class="workspace-header">

            <i class="bi bi-search me-2"></i>

            <span id="analysisTitle">

                Analisis

            </span>

        </div>

        <div class="card-body">

            <textarea

                id="analysis"

                name="analysis"

                class="form-control"

                rows="5"

                placeholder="Tuliskan analisis auditor..."></textarea>

        </div>

    </div>

</div>





    <!-- =======================================================
    REKOMENDASI
    ======================================================== -->

    <div class="col-12">

    <div class="card workspace-card">

        <div class="workspace-header">

            <i class="bi bi-lightbulb me-2"></i>

            <span id="recommendationTitle">

                Rekomendasi

            </span>

        </div>

        <div class="card-body">

            <textarea

                id="recommendation"

                name="recommendation"

                class="form-control"

                rows="5"

                placeholder="Tuliskan rekomendasi auditor..."></textarea>

        </div>

    </div>

</div>
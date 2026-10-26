<?php
require_once 'includes/functions.php';

// Sayılar
$post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$project_count = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$partner_count = $pdo->query("SELECT COUNT(*) FROM our_partners")->fetchColumn();
$slider_count = $pdo->query("SELECT COUNT(*) FROM sliders")->fetchColumn();
$company_info_count = $pdo->query("SELECT COUNT(*) FROM company_info_common")->fetchColumn();

// Kullanıcılar
$users = [];
$stmt = $pdo->query("SELECT id, username, email, image, created_at FROM users ORDER BY id DESC");
while ($row = $stmt->fetch()) $users[] = $row;

// Servisler
$services = [];
$stmt = $pdo->query("SELECT id, title_en, image FROM services ORDER BY id DESC");
while ($row = $stmt->fetch()) $services[] = $row;

// Company Infos
$company_infos = [];
$stmt = $pdo->query("SELECT * FROM company_info_common");
while ($row = $stmt->fetch()) $company_infos[] = $row;

// Vision, Mission, About Us (sadece İngilizce)
$vision = $pdo->query("SELECT  description FROM our_vision WHERE lang_code='en' LIMIT 1")->fetch();
$mission = $pdo->query("SELECT  description FROM our_mission WHERE lang_code='en' LIMIT 1")->fetch();
$about_us = $pdo->query("SELECT title, description FROM about_us WHERE lang_code='en' LIMIT 1")->fetch();
?>
<div class="container-fluid px-2 px-md-0">
    <!-- <h1 class="h2 mt-4 mb-4">Dashboard</h1> -->

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary"><i class="bi bi-file-earmark-text"></i> <?php echo $post_count; ?></div>
                    <div class="fw-semibold text-muted">Posts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success"><i class="bi bi-kanban"></i> <?php echo $project_count; ?></div>
                    <div class="fw-semibold text-muted">Projects</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-info"><i class="bi bi-people"></i> <?php echo $partner_count; ?></div>
                    <div class="fw-semibold text-muted">Partners</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning"><i class="bi bi-images"></i> <?php echo $slider_count; ?></div>
                    <div class="fw-semibold text-muted">Sliders</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users & Services -->
    <div class="row g-3 mb-4">
        <div class="col-lg-12 col-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-badge"></i> Users
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($user['image'])): ?>
                                                <img src="<?php echo $user['image']; ?>" class="rounded-circle" style="width:32px;height:32px;">
                                            <?php else: ?>
                                                <span class="bi bi-person-circle fs-3 text-muted"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-12 col-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-gear"></i> Services
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Title (EN)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($service['image'])): ?>
                                                <img src="<?php echo $service['image']; ?>" style="width:32px;height:32px;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($service['title_en']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($services)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No services found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>                              
    <!-- Company Infos -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-building"></i> Company Infos
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phone</th>
                                    <th>Phone1</th>
                                    <th>Phone2</th>
                                    <th>Email</th>
                                    <th>Email1</th>
                                    <th>Email2</th>
                                    <th>Logo Large</th>
                                    <th>Logo Small</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($company_infos as $info): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($info['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($info['phone1']); ?></td>
                                        <td><?php echo htmlspecialchars($info['phone2']); ?></td>
                                        <td><?php echo htmlspecialchars($info['email']); ?></td>
                                        <td><?php echo htmlspecialchars($info['email1']); ?></td>
                                        <td><?php echo htmlspecialchars($info['email2']); ?></td>
                                        <td>
                                            <?php if (!empty($info['logo_large'])): ?>
                                                <img src="<?php echo $info['logo_large']; ?>" style="width:32px;height:32px;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($info['logo_small'])): ?>
                                                <img src="<?php echo $info['logo_small']; ?>" style="width:32px;height:32px;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($info['created_at'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($info['updated_at'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($company_infos)): ?>
                                    <tr><td colspan="10" class="text-center text-muted">No company info found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional: Vision/Mission/About cards (commented) -->
    <!--
    <div class="row g-3 mb-4">
        ...
    </div>
    -->
</div>
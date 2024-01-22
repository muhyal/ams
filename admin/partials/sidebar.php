<?php global $siteName, $siteShortName;
/**
 * @copyright Copyright (c) 2024, KUTBU
 *
 * @author Muhammed Yalçınkaya <muhammed.yalcinkaya@kutbu.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
?>
<div class="container-fluid">
    <div class="row">
        <div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
            <div class="offcanvas-md offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="sidebarMenuLabel"><?php echo $siteName ?> - <?php echo $siteShortName ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/panel.php') ? 'active' : ''; ?>" href="/admin/panel.php">
                                <svg class="bi"><use xlink:href="#house-fill"/></svg>
                                Genel bakış
                            </a>
                        </li>

                        <!-- Muhasebe Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/accounting.php') ? 'active' : ''; ?>" href="/admin/accounting.php">
                                Muhasebe
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/accounting_list.php') ? 'active' : ''; ?>" href="/admin/accounting_list.php">
                                Ödemeler
                            </a>
                        </li>


                        <!-- Akademiler Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/academies.php') ? 'active' : ''; ?>" href="/admin/academies.php">
                                Akademiler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/course_plans.php') ? 'active' : ''; ?>" href="/admin/course_plans.php">
                                Ders Planları
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/introductory_courses.php') ? 'active' : ''; ?>" href="/admin/introductory_courses.php">
                                Tanışma Dersleri
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/rescheduled_courses.php') ? 'active' : ''; ?>" href="/admin/rescheduled_courses.php">
                                Telafi Dersleri
                            </a>
                        </li>



                        <!-- Kullanıcılar Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/users.php') ? 'active' : ''; ?>" href="/admin/users.php">
                                Kullanıcılar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/students.php') ? 'active' : ''; ?>" href="/admin/students.php">
                                Öğrenciler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/teachers.php') ? 'active' : ''; ?>" href="/admin/teachers.php">
                                Öğretmenler
                            </a>
                        </li>

                        <!-- Yönetim Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/admins.php') ? 'active' : ''; ?>" href="/admin/admins.php">
                                Yöneticiler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/user_academy_assignments.php') ? 'active' : ''; ?>" href="/admin/user_academy_assignments.php">
                                Kullanıcı - Akademi İlişkileri
                            </a>
                        </li>



                        <!-- Sınıflar Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/classes.php') ? 'active' : ''; ?>" href="/admin/classes.php">
                                <i class="bi bi-list"></i>
                                Sınıflar
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/courses.php') ? 'active' : ''; ?>" href="/admin/courses.php">
                                <i class="bi bi-list"></i>
                                Dersler
                            </a>
                        </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/birthdays.php') ? 'active' : ''; ?>" href="/admin/birthdays.php">
                    <svg class="bi"><use xlink:href="#people"/></svg>
                    Doğum Günleri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/agreement.php">
                    <svg class="bi"><use xlink:href="#plus-circle"/></svg>
                    Sözleşmeler
                </a>
            </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['SCRIPT_NAME'] == '/admin/reports.php') ? 'active' : ''; ?>" href="/admin/reports.php">
                                Raporlar
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase">
                        <span>Hazır raporlar</span>
                        <a class="link-secondary" href="#" aria-label="Add a new report">
                            <svg class="bi"><use xlink:href="#plus-circle"/></svg>
                        </a>
                    </h6>
                    <ul class="nav flex-column mb-auto">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="./../../reports/general_course_plans_report_for_the_current_month.php?generate_report">
                                <svg class="bi"><use xlink:href="#file-earmark-text"/></svg>
                                Bu Ayın Ders Planları
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="./../../reports/general_course_plans_report_for_last_month.php?generate_report">
                                <svg class="bi"><use xlink:href="#file-earmark-text"/></svg>
                                Geçen Ayın Ders Planları
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="./../../reports/general_accounting_report_for_the_current_month.php?generate_report">
                                <svg class="bi"><use xlink:href="#file-earmark-text"/></svg>
                                Bu Ayın Genel Raporu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="./../../reports/general_accounting_report_for_last_month.php?generate_report">
                                <svg class="bi"><use xlink:href="#file-earmark-text"/></svg>
                                Geçen Ayın Genel Raporu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == '/admin/academy_teachers.php') ? 'active' : ''; ?>" href="/admin/academy_teachers.php">
                                <i class="bi bi-people"></i>
                                Akademi Öğretmen/Ders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == '/admin/academy_students.php') ? 'active' : ''; ?>" href="/admin/academy_students.php">
                                <i class="bi bi-people"></i>
                                Akademi Öğrenci/Ders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == '/admin/academy_date_course_plans.php') ? 'active' : ''; ?>" href="/admin/academy_date_course_plans.php">
                                <i class="bi bi-people"></i>
                                Akademi/Tarih Ders Planları
                            </a>
                        </li>
                    </ul>
                    <hr class="my-3">

                    <ul class="nav flex-column mb-auto">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="/admin/profile_edit.php">
                                <svg class="bi"><use xlink:href="#gear-wide-connected"/></svg>
                                Ayarlar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="/admin/logout.php">
                                <svg class="bi"><use xlink:href="#door-closed"/></svg>
                                Oturumu kapat
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>



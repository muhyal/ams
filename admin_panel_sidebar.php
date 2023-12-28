<div class="container-fluid">
    <div class="row">
        <div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
            <div class="offcanvas-md offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="sidebarMenuLabel">Company name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a class="nav-link" href="admin_panel.php">
                                <svg class="bi"><use xlink:href="#house-fill"/></svg>
                                Genel bakış
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="search.php">
                                <svg class="bi"><use xlink:href="#search"/></svg>
                                Gelişmiş Arama
                            </a>
                        </li>

                        <!-- Muhasebe Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#accountingMenu">
                                <i class="bi bi-list"></i>
                                Muhasebe
                                <i class="bi bi-caret-down-fill"></i> <!-- Açık veya kapalı durumu göstermek için ok simgesi -->
                            </a>
                            <div class="collapse" id="accountingMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="accounting.php">
                                            <i class="bi bi-list"></i>
                                            Muhasebe
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="accounting_list.php">
                                            <i class="bi bi-list"></i>
                                            Muhasebe Listesi
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="accounting_reports.php">
                                            <i class="bi bi-graph-up"></i>
                                            Muhasebe Raporları
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>





                        <!-- Akademiler Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#academiesMenu">
                                <i class="bi bi-list"></i>
                                Akademiler
                                <i class="bi bi-caret-down-fill"></i>
                            </a>
                            <div class="collapse" id="academiesMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="academies.php">
                                            <i class="bi bi-list"></i>
                                            Akademiler
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="academy_teachers.php">
                                            <i class="bi bi-people"></i>
                                            Akademi Öğretmenleri
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="academy_students.php">
                                            <i class="bi bi-people"></i>
                                            Akademi Öğrencileri
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>



                        <!-- Kullanıcılar Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#usersMenu">
                                <i class="bi bi-people"></i>
                                Kullanıcı İşlemleri
                                <i class="bi bi-caret-down-fill"></i>
                            </a>
                            <div class="collapse" id="usersMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="user_list.php">
                                            Kullanıcılar
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_user.php">
                                            Kullanıcı Ekle
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="admin_list.php">
                                            Yöneticiler
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_admin.php">
                                            Yönetici Ekle
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="student_list.php">
                                            Öğrenciler
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_student.php">
                                            Öğrenci Ekle
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_parent.php">
                                            Veli Ekle
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="teachers_list.php">
                                            Öğretmenler
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_teacher.php">
                                            Öğretmen Ekle
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Sınıflar Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#classesMenu">
                                <i class="bi bi-list"></i>
                                Sınıflar
                                <i class="bi bi-caret-down-fill"></i>
                            </a>
                            <div class="collapse" id="classesMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="class_list.php">
                                            <i class="bi bi-list"></i>
                                            Sınıflar
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="add_class.php">
                                            <i class="bi bi-plus-circle"></i>
                                            Sınıf Ekle
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="courses.php">
                                <i class="bi bi-list"></i>
                                Dersler
                            </a>
                        </li>

            <li class="nav-item">
                <a class="nav-link" href="birthdays.php">
                    <svg class="bi"><use xlink:href="#people"/></svg>
                    Doğum Günleri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="agreement.php">
                    <svg class="bi"><use xlink:href="#plus-circle"/></svg>
                    Sözleşmeler
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
                            <a class="nav-link d-flex align-items-center gap-2" href="#">
                                <svg class="bi"><use xlink:href="#file-earmark-text"/></svg>
                                Bu ay alınan ödemeler
                            </a>
                        </li>
                    </ul>
                    <hr class="my-3">

                    <ul class="nav flex-column mb-auto">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="admin_profile_edit.php">
                                <svg class="bi"><use xlink:href="#gear-wide-connected"/></svg>
                                Ayarlar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="logout.php">
                                <svg class="bi"><use xlink:href="#door-closed"/></svg>
                                Oturumu kapat
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>



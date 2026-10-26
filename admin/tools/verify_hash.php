<?php
$hash = '$2y$10$4igfPPuJ1G2TvrcBBiwsLOk2.UEA3bvezWtE/o5bGLTXXR4J6nAai';
echo password_verify('admin-admin', $hash) ? "OK\n" : "FAIL\n";
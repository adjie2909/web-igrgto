<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>IGR GTO</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/logo_indogrosir.png')); ?>">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php endif; ?>
</head>

<body>
    <div class="app-shell">
        <?php if(!request()->is('login') && !request()->is('register')): ?>
            <header class="app-nav">
                <div class="app-nav__inner">
                    <div class="app-nav__top">
                        <div class="brand">
                            <div class="brand__mark">
                                <img src="<?php echo e(asset('assets/logo_indogrosir.png')); ?>" alt="Logo Indogrosir">
                            </div>
                            <div>
                                <div class="brand__title">Indogrosir Gorontalo</div>
                                <div class="brand__subtitle">Internal request and ticketing portal</div>
                            </div>
                        </div>

                        <nav class="nav-menu">
                            <a href="<?php echo e(route('dashboard')); ?>"
                                class="nav-link <?php echo e(request()->is('dashboard*') || request()->is('request') || request()->is('request/*') ? 'active' : ''); ?>">
                                Permintaan Barang
                            </a>

                            <?php if(auth()->guard()->check()): ?>
                                <?php if(!in_array(auth()->user()->role, ['SJM', 'SAM', 'SM'])): ?>
                                    <a href="<?php echo e(route('request-claim.index')); ?>"
                                        class="nav-link <?php echo e(request()->is('request-claim*') ? 'active' : ''); ?>">
                                        Pengambilan Barang
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <a href="<?php echo e(route('ticket.index')); ?>"
                                class="nav-link <?php echo e(request()->is('ticket*') ? 'active' : ''); ?>">
                                Ticketing Case
                            </a>
                        </nav>

                        <div class="nav-user">
                            <?php if(auth()->guard()->check()): ?>
                                <div class="nav-user__meta">
                                    <strong><?php echo e(auth()->user()->userid); ?></strong>
                                    <!-- <div class="nav-user__subline">
                                        <span><?php echo e(auth()->user()->userid); ?></span>
                                        <span class="nav-user__separator" aria-hidden="true"></span>
                                        <span><?php echo e(auth()->user()->role); ?></span>
                                    </div> -->
                                </div>

                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button class="btn btn-outline" type="submit">Logout</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>
        <?php endif; ?>

        <main class="page-shell">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <?php if(session('success')): ?>
        <div id="modalSuccess" class="modal modal-success">
            <div class="modal-content modal-content--success success-content">
                <div class="success-icon" aria-hidden="true"></div>
                <h3 class="success-title">Berhasil</h3>
                <p class="success-message">
                    <?php echo e(session('success')); ?>

                </p>
                <button class="btn btn-primary" id="btnCloseSuccess" type="button">
                    OK
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div id="modalError" class="modal modal-success">
            <div class="modal-content modal-content--success success-content">
                <div class="error-icon" aria-hidden="true">!</div>
                <h3 class="success-title">Peringatan</h3>
                <p class="success-message">
                    <?php echo e(session('error')); ?>

                </p>
                <button class="btn btn-primary" id="btnCloseError" type="button">
                    OK
                </button>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function () {

    <?php if(session('success')): ?>

        const modal = document.getElementById('modalSuccess');
        const btn = document.getElementById('btnCloseSuccess');

        if(modal){
            modal.classList.add('show');

            if(btn){
                btn.onclick = function(){
                    modal.classList.remove('show');
                }
            }

            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

    <?php endif; ?>

    <?php if(session('error')): ?>

        const modalError = document.getElementById('modalError');
        const btnError = document.getElementById('btnCloseError');

        if(modalError){
            modalError.classList.add('show');

            if(btnError){
                btnError.onclick = function(){
                    modalError.classList.remove('show');
                }
            }

            setTimeout(() => {
                modalError.classList.remove('show');
            }, 4000);
        }

    <?php endif; ?>

});
</script>
<?php /**PATH C:\laragon\www\web-igrgto\resources\views/layouts/app.blade.php ENDPATH**/ ?>
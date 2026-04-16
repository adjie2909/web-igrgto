<?php $__env->startSection('content'); ?>

<div style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div class="card" style="width:400px;">

        <div style="text-align:center; margin-bottom:20px;">
            <h2 style="margin-bottom:5px;">REGISTER</h2>
            <div style="font-size:13px; color:#64748b;">
                Buat akun baru
            </div>
        </div>

        <form method="POST" action="<?php echo e(route('register')); ?>">
            <?php echo csrf_field(); ?>

            <!-- NAME -->
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" id="name" class="input" value="<?php echo e(old('name')); ?>" required>
            </div>

            <!-- USERID -->
            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="userid" id="userid" class="input" value="<?php echo e(old('userid')); ?>" maxlength="3" required>
            </div>

            <!-- DIVISI -->
            <div class="form-group">
                <label>Divisi</label>
                <select name="division_id" class="input" required>
                    <option value="">-- Pilih Divisi --</option>
                    <?php $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($d->id); ?>" <?php echo e(old('division_id') == $d->id ? 'selected' : ''); ?>>
                            <?php echo e($d->nama_divisi); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="input" value="<?php echo e(old('email')); ?>">
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label>Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password" class="input">

                    <span onclick="togglePassword('password', this)"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                cursor:pointer; font-size:13px; color:#64748b;">
                        Show
                    </span>
                </div>
            </div>

            <!-- CONFIRM -->
            <div class="form-group">
                <label>Confirm Password</label>
                <div style="position:relative;">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input">

                    <span onclick="togglePassword('password_confirmation', this)"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                cursor:pointer; font-size:13px; color:#64748b;">
                        Show
                    </span>
                </div>
            </div>

            <!-- BUTTON -->
            <button class="btn btn-primary" style="width:100%;">
                Register
            </button>

            <div style="margin-top:15px; text-align:center; font-size:13px;">
                Sudah punya akun?
                <a href="<?php echo e(route('login')); ?>">Login</a>
            </div>

        </form>

    </div>

</div>

<div id="modalUseridUsed" class="modal">
    <div class="modal-content" style="max-width:420px;">
        <h3 style="margin-top:0;">User ID Sudah Dipakai</h3>
        <p style="font-size:14px; color:#64748b;">
            <?php echo e($errors->first('userid') ?: 'User ID sudah terpakai, silakan gunakan User ID lain.'); ?>

        </p>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="btnCloseUseridModal">OK</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // 🔥 USERID AUTO UPPERCASE
    const userid = document.getElementById('userid');
    if (userid) {
        userid.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
            this.style.border = '';
        });
    }

    // 🔥 NAME AUTO KAPITAL
    const name = document.getElementById('name');
    if (name) {
        name.addEventListener('input', function () {
            this.value = this.value.replace(/\b\w/g, c => c.toUpperCase());
        });
    }

    // jika userid kembar: tampilkan modal + kosongkan hanya kolom userid
    <?php if($errors->has('userid')): ?>
        const modalUseridUsed = document.getElementById('modalUseridUsed');
        const btnCloseUseridModal = document.getElementById('btnCloseUseridModal');

        if (userid) {
            userid.value = '';
            userid.style.border = '2px solid red';
        }

        if (modalUseridUsed) {
            modalUseridUsed.classList.add('show');
        }

        if (btnCloseUseridModal) {
            btnCloseUseridModal.onclick = function () {
                if (modalUseridUsed) {
                    modalUseridUsed.classList.remove('show');
                }
                if (userid) {
                    userid.focus();
                }
            };
        }

        if (modalUseridUsed) {
            modalUseridUsed.addEventListener('click', function (e) {
                if (e.target === modalUseridUsed) {
                    modalUseridUsed.classList.remove('show');
                    if (userid) {
                        userid.focus();
                    }
                }
            });
        }
    <?php endif; ?>

});

// 🔥 SHOW / HIDE PASSWORD
function togglePassword(id, el) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        el.innerText = "Hide";
    } else {
        input.type = "password";
        el.innerText = "Show";
    }
}
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\web-igrgto\resources\views/auth/register.blade.php ENDPATH**/ ?>
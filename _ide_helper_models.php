<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $nama_divisi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereNamaDivisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereUpdatedAt($value)
 */
	class Division extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $role
 * @property int $division_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $user_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivisionApprover whereUserId($value)
 */
	class DivisionApprover extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $userid
 * @property string $name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $division_id
 * @property-read \App\Models\Division|null $division
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserid($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $kode_barang
 * @property string $nama_barang
 * @property string|null $satuan
 * @property int $stok
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereKodeBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereNamaBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereSatuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereStok($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|barang whereUpdatedAt($value)
 */
	class barang extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $request_id
 * @property int|null $barang_id
 * @property int $qty
 * @property string|null $keterangan
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\barang|null $barang
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereBarangId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestdetail whereUpdatedAt($value)
 */
	class requestdetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $tanggal_request
 * @property int $status
 * @property int $current_approval_level
 * @property int|null $approved_by_level1
 * @property int|null $approved_by_level2
 * @property int|null $approved_by_level3
 * @property string|null $approved_at_level1
 * @property string|null $approved_at_level2
 * @property string|null $approved_at_level3
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $reject_reason
 * @property int|null $rejected_by
 * @property string|null $rejected_at
 * @property string|null $nomor_dokumen
 * @property string|null $nomor_serah
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\requestdetail> $details
 * @property-read int|null $details_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedAtLevel1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedAtLevel2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedAtLevel3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedByLevel1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedByLevel2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereApprovedByLevel3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereCurrentApprovalLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereNomorDokumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereNomorSerah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereRejectReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereTanggalRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|requestheader whereUserId($value)
 */
	class requestheader extends \Eloquent {}
}


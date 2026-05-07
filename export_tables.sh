#!/bin/bash
EXPORT_DIR="/Users/mandy/Desktop/Project/siaptek/siaptek/db_export"
mkdir -p "$EXPORT_DIR"

DB_USER="root"
DB_PASS="blackchat"
DB_NAME="siaptek"

TABLES=(
  admin_dinas apel apel_absen apel_peserta_dinas app_setting app_settings
  attachment_terms attendances_pegawai attendances_pegawai_test bidang
  config_potongan_tpp daftar_hadir_apel dinas dinas_back districts
  ganti_device izin izin_pegawai jadwal_apel jam_absen jenis_izin
  jenjang_pendidikan jml_hari_kerja kalender marriages menu_manager_role
  menu_managers model_has_permissions model_has_roles notifications
  pangkat_gol password_reset_tokens password_resets pegawai pengumuman
  pengumuman_penerima permission_role permissions position_pegawai
  provinces radius regencies religions role_has_permissions roles
  services services_type settings users versi villages
)

for TABLE in "${TABLES[@]}"; do
  echo "⏳ Exporting $TABLE..."
  mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" "$TABLE" --single-transaction --quick 2>/dev/null | gzip > "$EXPORT_DIR/${TABLE}.sql.gz"
  SIZE=$(ls -lh "$EXPORT_DIR/${TABLE}.sql.gz" | awk '{print $5}')
  echo "✅ $TABLE → $SIZE"
done

echo ""
echo "📁 Total export size:"
du -sh "$EXPORT_DIR"
echo ""
echo "📋 File list:"
ls -lhS "$EXPORT_DIR"

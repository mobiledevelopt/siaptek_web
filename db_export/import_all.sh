#!/bin/bash
# ===========================================
# Import semua tabel ke server dev
# Ganti DB_USER, DB_PASS, DB_NAME sesuai server dev
# ===========================================

DB_USER="root"
DB_PASS="password"
DB_NAME="siaptek"
IMPORT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "🔧 Import ke database: $DB_NAME"
echo "📁 Source: $IMPORT_DIR"
echo ""

# --- 1. Structure only (tabel monitoring, tanpa data) ---
echo "=== STRUCTURE ONLY ==="
for FILE in \
  telescope_entries_structure.sql.gz \
  telescope_entries_tags_structure.sql.gz \
  telescope_monitoring_structure.sql.gz \
  pulse_entries_structure.sql.gz \
  pulse_aggregates_structure.sql.gz \
  pulse_values_structure.sql.gz \
  personal_access_tokens_structure.sql.gz \
  log_langlong_structure.sql.gz \
  failed_jobs_structure.sql.gz \
  sessions_structure.sql.gz \
  jobs_structure.sql.gz
do
  TABLE="${FILE%_structure.sql.gz}"
  echo "⏳ $TABLE (structure)..."
  gunzip < "$IMPORT_DIR/$FILE" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null
  echo "✅ $TABLE"
done

echo ""

# --- 2. Data + Structure (tabel utama) ---
echo "=== DATA + STRUCTURE ==="
for FILE in \
  admin_dinas.sql.gz \
  apel.sql.gz \
  apel_absen.sql.gz \
  apel_peserta_dinas.sql.gz \
  app_setting.sql.gz \
  app_settings.sql.gz \
  attachment_terms.sql.gz \
  attendances_pegawai.sql.gz \
  attendances_pegawai_test.sql.gz \
  bidang.sql.gz \
  config_potongan_tpp.sql.gz \
  daftar_hadir_apel.sql.gz \
  dinas.sql.gz \
  dinas_back.sql.gz \
  districts.sql.gz \
  ganti_device.sql.gz \
  izin.sql.gz \
  izin_pegawai.sql.gz \
  jadwal_apel.sql.gz \
  jam_absen.sql.gz \
  jenis_izin.sql.gz \
  jenjang_pendidikan.sql.gz \
  jml_hari_kerja.sql.gz \
  kalender.sql.gz \
  marriages.sql.gz \
  menu_manager_role.sql.gz \
  menu_managers.sql.gz \
  model_has_permissions.sql.gz \
  model_has_roles.sql.gz \
  notifications.sql.gz \
  pangkat_gol.sql.gz \
  password_reset_tokens.sql.gz \
  password_resets.sql.gz \
  pegawai.sql.gz \
  pengumuman.sql.gz \
  pengumuman_penerima.sql.gz \
  permission_role.sql.gz \
  permissions.sql.gz \
  position_pegawai.sql.gz \
  provinces.sql.gz \
  radius.sql.gz \
  regencies.sql.gz \
  religions.sql.gz \
  role_has_permissions.sql.gz \
  roles.sql.gz \
  services.sql.gz \
  services_type.sql.gz \
  settings.sql.gz \
  users.sql.gz \
  versi.sql.gz \
  villages.sql.gz
do
  TABLE="${FILE%.sql.gz}"
  echo "⏳ $TABLE..."
  gunzip < "$IMPORT_DIR/$FILE" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null
  echo "✅ $TABLE"
done

echo ""
echo "🎉 Import selesai!"

#!/bin/bash
set -euo pipefail

REMOTE_APP_DIR="$1"
ZIP_FILE="$2"
TS="$3"

BACKUP_DIR="${REMOTE_APP_DIR}_backups"
WORK_DIR="/tmp/pldl_release_${TS}"

echo "[REMOTE] start"
echo "[REMOTE] app dir: ${REMOTE_APP_DIR}"
echo "[REMOTE] zip: ${ZIP_FILE}"

if [ ! -f "${ZIP_FILE}" ]; then
  echo "[REMOTE][ERROR] zip not found"
  exit 1
fi

mkdir -p "${BACKUP_DIR}"
mkdir -p "${WORK_DIR}"
mkdir -p "${REMOTE_APP_DIR}"

if [ -d "${REMOTE_APP_DIR}/app" ]; then
  echo "[REMOTE] backup current app"
  tar \
    --exclude='.env' \
    --exclude='storage/*' \
    --exclude='vendor/*' \
    --exclude='node_modules/*' \
    -czf "${BACKUP_DIR}/backup_${TS}.tar.gz" \
    -C "${REMOTE_APP_DIR}" .
fi

echo "[REMOTE] unzip package"
unzip -oq "${ZIP_FILE}" -d "${WORK_DIR}"

for d in app bootstrap config database public resources routes; do
  if [ -d "${REMOTE_APP_DIR}/${d}" ]; then
    rm -rf "${REMOTE_APP_DIR:?}/${d}"
  fi
done

for d in app bootstrap config database public resources routes; do
  if [ -d "${WORK_DIR}/${d}" ]; then
    cp -a "${WORK_DIR}/${d}" "${REMOTE_APP_DIR}/"
  fi
done

for f in artisan composer.json composer.lock package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js; do
  if [ -f "${WORK_DIR}/${f}" ]; then
    cp -f "${WORK_DIR}/${f}" "${REMOTE_APP_DIR}/"
  fi
done

mkdir -p "${REMOTE_APP_DIR}/storage"
mkdir -p "${REMOTE_APP_DIR}/bootstrap/cache"

find "${REMOTE_APP_DIR}/bootstrap/cache" -mindepth 1 -maxdepth 1 ! -name '.gitignore' -exec rm -rf {} +

rm -rf "${WORK_DIR}"
rm -f "${ZIP_FILE}"

echo "[REMOTE] done"
echo "[REMOTE] backup: ${BACKUP_DIR}/backup_${TS}.tar.gz"

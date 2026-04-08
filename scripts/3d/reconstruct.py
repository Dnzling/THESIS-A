#!/usr/bin/env python
"""
3D Reconstruction Pipeline Skeleton
-----------------------------------
This script is a thin wrapper intended to be called from Laravel's job.
It prepares directories and provides hooks for COLMAP/OpenMVG/OpenMVS.

Usage:
  python reconstruct.py --input <image_dir> --output <output_file> [--work <work_dir>] [--engine colmap|openmvg]
"""

import argparse
import os
import shutil
import subprocess
import sys
from pathlib import Path


def run(cmd, cwd=None, env=None):
    print(f"[run] {cmd}")
    result = subprocess.run(
        cmd,
        cwd=cwd,
        env=env,
        shell=isinstance(cmd, str),
        capture_output=True,
        text=True,
    )
    if result.stdout:
        print(result.stdout)
    if result.stderr:
        print(result.stderr, file=sys.stderr)
    if result.returncode != 0:
        raise RuntimeError(
            f"Command failed: {cmd}\n"
            f"stdout:\n{result.stdout}\n"
            f"stderr:\n{result.stderr}"
        )


def ensure_dir(path: Path):
    path.mkdir(parents=True, exist_ok=True)


def _quote_arg(arg: str) -> str:
    escaped = arg.replace('"', '\\"')
    return f"\"{escaped}\""


def colmap_cmd(colmap_bin: str, *args: str):
    bin_path = str(colmap_bin)
    if bin_path.lower().endswith((".bat", ".cmd")):
        quoted_bin = _quote_arg(bin_path)
        quoted_args = " ".join(_quote_arg(a) for a in args)
        # Use cmd /s /c to preserve quoting with spaces.
        return f'cmd /s /c "{quoted_bin} {quoted_args}"'
    return [bin_path, *args]


def _sanitize_and_copy_image(src: Path, dst: Path):
    try:
        from PIL import Image, ImageOps
    except Exception:
        shutil.copy2(src, dst)
        return

    try:
        with Image.open(src) as img:
            # Fix invalid EXIF orientation and drop metadata by re-encoding without EXIF.
            img = ImageOps.exif_transpose(img)
            if img.mode not in ("RGB", "L"):
                img = img.convert("RGB")

            suffix = src.suffix.lower()
            if suffix in (".jpg", ".jpeg"):
                img.save(dst, format="JPEG", quality=92, optimize=True)
            elif suffix == ".png":
                img.save(dst, format="PNG", optimize=True)
            else:
                # Fallback to JPEG for other formats.
                dst = dst.with_suffix(".jpg")
                img.save(dst, format="JPEG", quality=92, optimize=True)
    except Exception:
        shutil.copy2(src, dst)


def copy_images(input_dir: Path, images_dir: Path):
    ensure_dir(images_dir)
    for item in sorted(input_dir.iterdir()):
        if item.is_file():
            _sanitize_and_copy_image(item, images_dir / item.name)


def _estimate_focal_px(images_dir: Path) -> int:
    """
    Estimate a reasonable focal length in pixels when EXIF is missing.
    Uses 1.2 * max(width, height) of the first image.
    """
    try:
        from PIL import Image
    except Exception:
        return 0

    for item in sorted(images_dir.iterdir()):
        if item.is_file():
            try:
                with Image.open(item) as img:
                    w, h = img.size
                    return int(max(w, h) * 1.2)
            except Exception:
                continue
    return 0


def colmap_pipeline(images_dir: Path, work_dir: Path, output_file: Path, colmap_bin: str):
    """
    COLMAP pipeline (CPU-friendly).
    Generates a dense point cloud and Poisson mesh (PLY).
    """
    db_path = work_dir / "database.db"
    sparse_dir = work_dir / "sparse"
    dense_dir = work_dir / "dense"
    ensure_dir(sparse_dir)
    ensure_dir(dense_dir)

    fused_ply = dense_dir / "fused.ply"
    meshed_ply = dense_dir / "meshed-poisson.ply"

    run(colmap_cmd(
        colmap_bin,
        "feature_extractor",
        "--database_path", str(db_path),
        "--image_path", str(images_dir),
        "--ImageReader.single_camera", "1",
        "--FeatureExtraction.max_image_size", "1600",
        "--FeatureExtraction.num_threads", "2",
        "--FeatureExtraction.use_gpu", "0",
    ))
    run(colmap_cmd(
        colmap_bin,
        "exhaustive_matcher",
        "--database_path", str(db_path),
        "--FeatureMatching.use_gpu", "0",
    ))
    run(colmap_cmd(
        colmap_bin,
        "mapper",
        "--database_path", str(db_path),
        "--image_path", str(images_dir),
        "--output_path", str(sparse_dir),
    ))

    # Use the first sparse model (0)
    sparse_model = sparse_dir / "0"
    if not sparse_model.exists():
        raise RuntimeError("Sparse model not found at expected path.")

    run(colmap_cmd(
        colmap_bin,
        "image_undistorter",
        "--image_path", str(images_dir),
        "--input_path", str(sparse_model),
        "--output_path", str(dense_dir),
        "--output_type", "COLMAP",
        "--max_image_size", "2000",
    ))
    run(colmap_cmd(
        colmap_bin,
        "patch_match_stereo",
        "--workspace_path", str(dense_dir),
        "--workspace_format", "COLMAP",
        "--PatchMatchStereo.geom_consistency", "true",
    ))
    run(colmap_cmd(
        colmap_bin,
        "stereo_fusion",
        "--workspace_path", str(dense_dir),
        "--workspace_format", "COLMAP",
        "--input_type", "geometric",
        "--output_path", str(fused_ply),
    ))
    run(colmap_cmd(
        colmap_bin,
        "poisson_mesher",
        "--input_path", str(fused_ply),
        "--output_path", str(meshed_ply),
    ))

    if not meshed_ply.exists():
        raise RuntimeError("Poisson mesh was not created.")

    # Output mesh as PLY (default)
    shutil.copy2(meshed_ply, output_file)


def _resolve_exe(bin_or_path: str | None, exe_name: str) -> str:
    if not bin_or_path:
        return exe_name
    path = Path(bin_or_path)
    if path.is_dir():
        candidate = path / exe_name
        if candidate.exists():
            return str(candidate)
        candidate_exe = candidate.with_suffix(".exe")
        if candidate_exe.exists():
            return str(candidate_exe)
        return str(candidate)
    return str(path)


def _resolve_openmvg_sensor_db(openmvg_bin: str | None) -> str | None:
    # Try common locations relative to openMVG bin
    if openmvg_bin:
        bin_path = Path(openmvg_bin)
        if bin_path.is_dir():
            # Typical locations
            candidates = [
                bin_path.parent / "share" / "openMVG" / "sensor_width_camera_database.txt",
                bin_path.parent / "share" / "openMVG" / "sensor_width_camera_database.txt",
                bin_path.parent / "openMVG" / "sensor_width_camera_database.txt",
                bin_path / "sensor_width_camera_database.txt",
            ]
            for candidate in candidates:
                if candidate.exists():
                    return str(candidate)
    return None


def openmvg_openmvs_pipeline(
    images_dir: Path,
    work_dir: Path,
    output_file: Path,
    openmvg_bin: str | None,
    openmvs_bin: str | None,
    sensors_db: str | None,
):
    """
    OpenMVG + OpenMVS pipeline (CPU-friendly).
    Requires OpenMVG and OpenMVS installed locally.
    """
    matches_dir = work_dir / "matches"
    reconstruction_dir = work_dir / "reconstruction"
    ensure_dir(matches_dir)
    ensure_dir(reconstruction_dir)

    if not sensors_db:
        sensors_db = _resolve_openmvg_sensor_db(openmvg_bin)
    if not sensors_db or not Path(sensors_db).exists():
        raise RuntimeError(
            "OpenMVG sensor database not found. Provide --openmvg_sensors path to sensor_width_camera_database.txt."
        )

    # Resolve executables
    sfm_init = _resolve_exe(openmvg_bin, "openMVG_main_SfMInit_ImageListing")
    compute_features = _resolve_exe(openmvg_bin, "openMVG_main_ComputeFeatures")
    compute_matches = _resolve_exe(openmvg_bin, "openMVG_main_ComputeMatches")
    geometric_filter = _resolve_exe(openmvg_bin, "openMVG_main_GeometricFilter")
    incremental_sfm = _resolve_exe(openmvg_bin, "openMVG_main_IncrementalSfM")
    if not Path(incremental_sfm).exists():
        incremental_sfm = _resolve_exe(openmvg_bin, "openMVG_main_SfM")
    colorize = _resolve_exe(openmvg_bin, "openMVG_main_ComputeSfM_DataColor")
    to_openmvs = _resolve_exe(openmvg_bin, "openMVG_main_openMVG2openMVS")

    densify = _resolve_exe(openmvs_bin, "DensifyPointCloud")
    reconstruct = _resolve_exe(openmvs_bin, "ReconstructMesh")
    texture = _resolve_exe(openmvs_bin, "TextureMesh")

    env = os.environ.copy()
    env.setdefault("OMP_NUM_THREADS", "1")
    env.setdefault("OPENMVG_NUM_THREADS", "1")

    # 1) Initialize image listing
    focal_px = _estimate_focal_px(images_dir)
    run([
        sfm_init,
        "-i", str(images_dir),
        "-o", str(matches_dir),
        "-d", str(sensors_db),
        "-c", "1",  # 1 = pinhole, use single camera
        "-f", str(focal_px if focal_px > 0 else 1200),
    ], env=env)

    # 2) Compute features
    run([
        compute_features,
        "-i", str(matches_dir / "sfm_data.json"),
        "-o", str(matches_dir),
        "-m", "SIFT",
        "-p", "NORMAL",
    ], env=env)

    # 3) Compute matches (putative)
    matches_f = matches_dir / "matches.f.bin"
    try:
        run([
            compute_matches,
            "-i", str(matches_dir / "sfm_data.json"),
            "-o", str(matches_f),
        ], env=env)
    except RuntimeError:
        # Fallback: reduce matcher complexity to avoid OpenMP crashes on some Windows builds.
        run([
            compute_matches,
            "-i", str(matches_dir / "sfm_data.json"),
            "-o", str(matches_f),
            "-n", "BRUTEFORCEL2",
            "-P", "200",
        ], env=env)

    # 4) Geometric filtering (robust matches)
    matches_e = matches_dir / "matches.e.bin"
    try:
        run([
            geometric_filter,
            "-i", str(matches_dir / "sfm_data.json"),
            "-m", str(matches_f),
            "-o", str(matches_e),
            "-g", "f",
        ], env=env)
    except RuntimeError as e:
        # Some Windows builds can fail here (or require Graphviz). Fallback to putative matches.
        print(f"[warn] GeometricFilter failed, falling back to matches.f.bin. Reason: {e}", file=sys.stderr)
        shutil.copy2(matches_f, matches_e)

    # 5) Incremental SfM
    run([
        incremental_sfm,
        "-i", str(matches_dir / "sfm_data.json"),
        "-m", str(matches_dir),
        "-o", str(reconstruction_dir),
    ], env=env)

    # 6) Colorize sparse reconstruction
    run([
        colorize,
        "-i", str(reconstruction_dir / "sfm_data.bin"),
        "-o", str(reconstruction_dir / "sfm_data_color.bin"),
    ], env=env)

    # 7) Convert to OpenMVS
    scene_mvs = work_dir / "scene.mvs"
    run([
        to_openmvs,
        "-i", str(reconstruction_dir / "sfm_data.bin"),
        "-o", str(scene_mvs),
        "-d", str(images_dir),
    ], env=env)

    # 7) OpenMVS Densify -> Mesh -> Texture
    run([densify, str(scene_mvs)], cwd=work_dir, env=env)
    dense_mvs = work_dir / "scene_dense.mvs"
    run([reconstruct, str(dense_mvs)], cwd=work_dir, env=env)
    dense_mesh_mvs = work_dir / "scene_dense_mesh.mvs"
    run([texture, str(dense_mesh_mvs)], cwd=work_dir, env=env)

    # Pick output mesh
    candidates = [
        work_dir / "scene_dense_mesh_texture.ply",
        work_dir / "scene_dense_mesh_texture.obj",
        work_dir / "scene_dense_mesh.ply",
        work_dir / "scene_dense_mesh.obj",
    ]
    mesh_file = next((c for c in candidates if c.exists()), None)
    if not mesh_file:
        raise RuntimeError("OpenMVS mesh output not found.")

    shutil.copy2(mesh_file, output_file)


def _find_meshroom_output(meshroom_dir: Path) -> Path | None:
    # Meshroom outputs texturedMesh.* inside MeshroomCache/Texturing/<hash>/
    candidates = list(meshroom_dir.rglob("texturedMesh.obj")) + list(meshroom_dir.rglob("texturedMesh.ply"))
    if candidates:
        # Choose the newest file
        return max(candidates, key=lambda p: p.stat().st_mtime)
    return None


def meshroom_pipeline(
    images_dir: Path,
    work_dir: Path,
    output_file: Path,
    meshroom_exec: str | None,
):
    """
    Meshroom (AliceVision) pipeline (CPU-friendly).
    Requires Meshroom installed locally (meshroom_photogrammetry).
    """
    meshroom_root = work_dir / "meshroom"
    ensure_dir(meshroom_root)

    meshroom_photogrammetry = _resolve_exe(meshroom_exec, "meshroom_photogrammetry")

    env = os.environ.copy()
    env.setdefault("OMP_NUM_THREADS", "1")

    run([
        meshroom_photogrammetry,
        "--input", str(images_dir),
        "--output", str(meshroom_root),
    ], env=env)

    mesh_file = _find_meshroom_output(meshroom_root)
    if not mesh_file:
        raise RuntimeError("Meshroom output not found (texturedMesh.obj/ply).")

    shutil.copy2(mesh_file, output_file)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True, help="Directory containing input images")
    parser.add_argument("--output", required=True, help="Path to output model file (e.g., model.glb)")
    parser.add_argument("--work", default=None, help="Working directory (defaults to sibling of output)")
    parser.add_argument("--engine", default="colmap", choices=["colmap", "openmvg", "meshroom"])
    parser.add_argument("--colmap", default="colmap", help="Path to colmap executable (or COLMAP.bat)")
    parser.add_argument("--openmvg", default=None, help="Path to OpenMVG bin directory (or specific exe)")
    parser.add_argument("--openmvs", default=None, help="Path to OpenMVS bin directory (or specific exe)")
    parser.add_argument("--meshroom", default=None, help="Path to Meshroom executable or install folder")
    parser.add_argument(
        "--openmvg_sensors",
        default=None,
        help="Path to OpenMVG sensor_width_camera_database.txt",
    )
    args = parser.parse_args()

    input_dir = Path(args.input).resolve()
    output_file = Path(args.output).resolve()
    work_dir = Path(args.work).resolve() if args.work else output_file.parent / "work"

    if not input_dir.exists() or not input_dir.is_dir():
        print(f"Input directory not found: {input_dir}", file=sys.stderr)
        return 2

    ensure_dir(output_file.parent)
    ensure_dir(work_dir)

    images_dir = work_dir / "images"
    copy_images(input_dir, images_dir)

    if args.engine == "colmap":
        colmap_pipeline(images_dir, work_dir, output_file, args.colmap)
    elif args.engine == "openmvg":
        openmvg_openmvs_pipeline(
            images_dir,
            work_dir,
            output_file,
            args.openmvg,
            args.openmvs,
            args.openmvg_sensors,
        )
    else:
        meshroom_pipeline(
            images_dir,
            work_dir,
            output_file,
            args.meshroom,
        )

    # If your pipeline outputs OBJ/PLY, convert to GLB here.
    # TODO: Add conversion step (e.g., using obj2gltf or Blender).

    if not output_file.exists():
        raise RuntimeError("Output file was not created.")

    print(f"Reconstruction complete: {output_file}")
    return 0


if __name__ == "__main__":
    sys.exit(main())

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
import shutil
import subprocess
import sys
from pathlib import Path


def run(cmd, cwd=None):
    print(f"[run] {cmd}")
    result = subprocess.run(cmd, cwd=cwd, shell=isinstance(cmd, str))
    if result.returncode != 0:
        raise RuntimeError(f"Command failed: {cmd}")


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


def openmvg_openmvs_pipeline(images_dir: Path, work_dir: Path, output_file: Path):
    """
    OpenMVG + OpenMVS pipeline placeholder.
    Replace the commands below with your actual OpenMVG/OpenMVS steps.
    """
    matches_dir = work_dir / "matches"
    reconstruction_dir = work_dir / "reconstruction"
    ensure_dir(matches_dir)
    ensure_dir(reconstruction_dir)

    # TODO: Replace these with your real OpenMVG/OpenMVS commands.
    # Example skeleton (commented):
    # run(f"openMVG_main_SfMInit_ImageListing -i {images_dir} -o {matches_dir} -d /path/to/sensor_width_camera_database.txt")
    # run(f"openMVG_main_ComputeFeatures -i {matches_dir}/sfm_data.json -o {matches_dir}")
    # run(f"openMVG_main_ComputeMatches -i {matches_dir}/sfm_data.json -o {matches_dir}")
    # run(f"openMVG_main_IncrementalSfM -i {matches_dir}/sfm_data.json -m {matches_dir} -o {reconstruction_dir}")
    # run(f"openMVG_main_ComputeSfM_DataColor -i {reconstruction_dir}/sfm_data.bin -o {reconstruction_dir}/sfm_data_color.bin")
    # run(f"openMVG_main_openMVG2openMVS -i {reconstruction_dir}/sfm_data.bin -o {work_dir}/scene.mvs")
    # run(f"DensifyPointCloud {work_dir}/scene.mvs")
    # run(f"ReconstructMesh {work_dir}/scene_dense.mvs")
    # run(f"TextureMesh {work_dir}/scene_dense_mesh.mvs")

    # Placeholder output
    raise RuntimeError("OpenMVG/OpenMVS pipeline not configured. Edit reconstruct.py to enable.")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True, help="Directory containing input images")
    parser.add_argument("--output", required=True, help="Path to output model file (e.g., model.glb)")
    parser.add_argument("--work", default=None, help="Working directory (defaults to sibling of output)")
    parser.add_argument("--engine", default="colmap", choices=["colmap", "openmvg"])
    parser.add_argument("--colmap", default="colmap", help="Path to colmap executable (or COLMAP.bat)")
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
    else:
        openmvg_openmvs_pipeline(images_dir, work_dir, output_file)

    # If your pipeline outputs OBJ/PLY, convert to GLB here.
    # TODO: Add conversion step (e.g., using obj2gltf or Blender).

    if not output_file.exists():
        raise RuntimeError("Output file was not created.")

    print(f"Reconstruction complete: {output_file}")
    return 0


if __name__ == "__main__":
    sys.exit(main())

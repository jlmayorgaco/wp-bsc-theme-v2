"""Validate tar member names and link targets before extraction to an empty directory."""
import posixpath
import sys
import tarfile


def safe(name):
    normalized = posixpath.normpath(name.replace('\\', '/'))
    return not (normalized.startswith('/') or normalized == '..' or normalized.startswith('../') or ':' in normalized.split('/')[0])


with tarfile.open(sys.argv[1], 'r:*') as archive:
    members = archive.getmembers()
    links = set()
    for member in members:
        if not safe(member.name) or member.isdev() or member.isfifo():
            raise SystemExit('Unsafe archive member')
        if member.issym() or member.islnk():
            target = posixpath.join(posixpath.dirname(member.name), member.linkname) if member.issym() else member.linkname
            if not safe(target):
                raise SystemExit('Unsafe archive link')
            links.add(posixpath.normpath(member.name))
    for member in members:
        parent = posixpath.dirname(posixpath.normpath(member.name))
        while parent not in ('', '.', '/'):
            if parent in links:
                raise SystemExit('Archive writes through a link')
            parent = posixpath.dirname(parent)
print('Archive paths OK')

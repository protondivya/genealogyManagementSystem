import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';

function genderClass(gender) {
    if (gender === 'female') return 'border-rose-300 bg-rose-50';
    if (gender === 'male') return 'border-sky-300 bg-sky-50';
    return 'border-stone-300 bg-white';
}

export default function TreeIndex({ tree, people, root, depth, canContribute }) {
    const [scale, setScale] = useState(1);
    const [offset, setOffset] = useState({ x: 40, y: 40 });
    const [dragging, setDragging] = useState(false);
    const [collapsed, setCollapsed] = useState({});
    const dragOrigin = useRef(null);

    const layout = useMemo(() => {
        const nodes = tree.nodes || [];
        const byGen = {};
        nodes.forEach((n) => {
            if (collapsed[n.id]) {
                return;
            }
            byGen[n.generation] = byGen[n.generation] || [];
            byGen[n.generation].push(n);
        });
        const gens = Object.keys(byGen).map(Number).sort((a, b) => a - b);
        const positions = {};
        gens.forEach((gen, gi) => {
            byGen[gen].forEach((n, i) => {
                positions[n.id] = { x: 40 + i * 210, y: 40 + gi * 160, node: n };
            });
        });
        const lines = [];
        nodes.forEach((n) => {
            (n.child_ids || []).forEach((cid) => {
                if (positions[n.id] && positions[cid] && !collapsed[n.id]) {
                    lines.push({ from: positions[n.id], to: positions[cid] });
                }
            });
            (n.spouse_ids || []).forEach((sid) => {
                if (positions[n.id] && positions[sid] && n.id < sid) {
                    lines.push({ from: positions[n.id], to: positions[sid], spouse: true });
                }
            });
        });
        return { positions, lines };
    }, [tree, collapsed]);

    const onPointerDown = (e) => {
        setDragging(true);
        dragOrigin.current = { x: e.clientX - offset.x, y: e.clientY - offset.y };
    };
    const onPointerMove = (e) => {
        if (!dragging || !dragOrigin.current) return;
        setOffset({ x: e.clientX - dragOrigin.current.x, y: e.clientY - dragOrigin.current.y });
    };
    const onPointerUp = () => {
        setDragging(false);
        dragOrigin.current = null;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="font-serif text-2xl">Family tree</h1>
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="text-sm">
                            Root person
                            <select
                                className="ml-2 rounded-md border-stone-300 text-sm"
                                value={root || ''}
                                onChange={(e) => router.get(route('tree.show'), { root: e.target.value, depth })}
                            >
                                {people.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </label>
                        <label className="text-sm">
                            Depth
                            <select
                                className="ml-2 rounded-md border-stone-300 text-sm"
                                value={depth}
                                onChange={(e) => router.get(route('tree.show'), { root, depth: e.target.value })}
                            >
                                {[2, 3, 4, 5, 6, 8].map((d) => <option key={d} value={d}>{d}</option>)}
                            </select>
                        </label>
                        <Link href={route('export.index')} className="rounded-full border border-forest px-4 py-2 text-sm text-forest">Export / print</Link>
                    </div>
                </div>
            }
        >
            <Head title="Family tree" />
            <div className="px-4 py-6">
                {!tree.nodes?.length ? (
                    <div className="mx-auto max-w-xl rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-stone-200">
                        <h2 className="font-serif text-2xl">Your tree is empty</h2>
                        <p className="mt-2 text-stone-600">Start by adding yourself, then add your parents.</p>
                        {canContribute && (
                            <Link href={route('people.create')} className="mt-6 inline-block rounded-full bg-forest px-5 py-3 text-white">
                                Add a family member
                            </Link>
                        )}
                    </div>
                ) : (
                    <>
                        <div className="mb-3 flex gap-2">
                            <button type="button" className="rounded-full bg-white px-3 py-1 ring-1 ring-stone-200" onClick={() => setScale((s) => Math.min(2, s + 0.1))}>Zoom in</button>
                            <button type="button" className="rounded-full bg-white px-3 py-1 ring-1 ring-stone-200" onClick={() => setScale((s) => Math.max(0.5, s - 0.1))}>Zoom out</button>
                            <button type="button" className="rounded-full bg-white px-3 py-1 ring-1 ring-stone-200" onClick={() => { setScale(1); setOffset({ x: 40, y: 40 }); }}>Reset</button>
                        </div>
                        <div
                            className={`tree-canvas relative h-[70vh] rounded-2xl ring-1 ring-stone-200 ${dragging ? 'dragging' : ''}`}
                            onPointerDown={onPointerDown}
                            onPointerMove={onPointerMove}
                            onPointerUp={onPointerUp}
                            onPointerLeave={onPointerUp}
                        >
                            <div style={{ transform: `translate(${offset.x}px, ${offset.y}px) scale(${scale})`, transformOrigin: '0 0' }} className="relative h-full w-full">
                                <svg className="pointer-events-none absolute inset-0 h-[2000px] w-[4000px]">
                                    {layout.lines.map((line, i) => (
                                        <line
                                            key={i}
                                            x1={line.from.x + 80}
                                            y1={line.from.y + 40}
                                            x2={line.to.x + 80}
                                            y2={line.to.y + 40}
                                            stroke={line.spouse ? '#c4a574' : '#8a8175'}
                                            strokeWidth="2"
                                            strokeDasharray={line.spouse ? '6 4' : undefined}
                                        />
                                    ))}
                                </svg>
                                {Object.values(layout.positions).map(({ x, y, node }) => (
                                    <div key={node.id} className="person-node absolute" style={{ left: x, top: y }}>
                                        <Link
                                            href={route('people.show', node.id)}
                                            className={`block rounded-xl border p-3 shadow-sm ${genderClass(node.gender)} ${node.id === tree.root_id ? 'ring-2 ring-forest' : ''}`}
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            <div className="flex items-center gap-2">
                                                {node.photo_url ? (
                                                    <img src={node.photo_url} alt="" className="h-8 w-8 rounded-full object-cover" />
                                                ) : (
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm">{(node.full_name || '?')[0]}</div>
                                                )}
                                                <div>
                                                    <div className="text-sm font-semibold leading-tight">{node.full_name}</div>
                                                    <div className="text-xs text-stone-500">{node.life_span}</div>
                                                </div>
                                            </div>
                                        </Link>
                                        {node.child_ids?.length > 0 && (
                                            <button
                                                type="button"
                                                className="mt-1 text-xs text-forest underline"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setCollapsed((c) => ({ ...c, [node.id]: !c[node.id] }));
                                                }}
                                            >
                                                {collapsed[node.id] ? 'Expand' : 'Collapse'}
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

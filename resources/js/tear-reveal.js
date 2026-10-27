/**
 * Hero "tear reveal": the paper with the Lahiya logo cracks and tears in two,
 * and three phones wearing Lahiya cases rise from behind and fan out.
 * Progress follows the scroll position of the wrapper element; the phones
 * tilt slightly towards the pointer.
 *
 * Markup lives in resources/views/components/store/tear-reveal.blade.php and
 * exposes its parts through [data-tear="..."] attributes.
 */

const clamp = (value) => (value < 0 ? 0 : value > 1 ? 1 : value);

const smooth = (from, to, value) => {
    const t = clamp((value - from) / (to - from));

    return t * t * (3 - 2 * t);
};

const toPath = (points, close = false) =>
    'M' + points.map(([x, y]) => `${x.toFixed(1)} ${y.toFixed(1)}`).join('L') + (close ? 'Z' : '');

const seeded = (seed) => {
    let state = seed >>> 0;

    return () => {
        state = (Math.imul(state, 1664525) + 1013904223) >>> 0;

        return state / 4294967296;
    };
};

const buildGeometry = () => {
    const random = seeded(11);
    const jitter = (amount) => (random() - 0.5) * amount;
    const slope = Math.tan((-5 * Math.PI) / 180);
    const line = [];

    for (let x = -200; x <= 1200; x += 9) {
        const wave = Math.sin(x * 0.021) * 9 + Math.sin(x * 0.057 + 1.3) * 4;
        const jag = jitter(6) + (random() < 0.08 ? jitter(24) : 0);

        line.push([x, 290 + (x - 500) * slope + wave + jag]);
    }

    const full = [[-3000, line[0][1]], ...line, [4000, line[line.length - 1][1]]];
    const fibres = line.map(() => 2.5 + random() * 7);
    let length = 0;

    for (let i = 1; i < line.length; i++) {
        length += Math.hypot(line[i][0] - line[i - 1][0], line[i][1] - line[i - 1][1]);
    }

    return {
        line,
        fibres,
        length,
        lineD: toPath(line),
        topClip: toPath([[-3000, -4000], ...full, [4000, -4000]], true),
        // Overlap the halves by 3 units so no anti-aliased seam shows before the tear.
        bottomClip: toPath([[-3000, 4000], ...full.map(([x, y]) => [x, y - 3]), [4000, 4000]], true),
    };
};

const mount = (root) => {
    const refs = {};

    root.querySelectorAll('[data-tear]').forEach((element) => {
        element.dataset.tear.split(/\s+/).forEach((name) => (refs[name] ??= []).push(element));
    });

    const set = (name, attribute, value) => refs[name]?.forEach((element) => element.setAttribute(attribute, value));
    const geometry = buildGeometry();

    set('top-clip', 'd', geometry.topClip);
    set('bottom-clip', 'd', geometry.bottomClip);
    set('line', 'd', geometry.lineD);
    set('crack', 'stroke-dasharray', `${geometry.length.toFixed(0)} ${geometry.length.toFixed(0)}`);

    const stage = root.querySelector('[data-tear-stage]') ?? root;
    const scene = root.querySelector('[data-tear-scene]');
    const rig = root.querySelector('[data-tear-rig]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const state = { target: [0, 0], look: [0, 0], lastPointer: 0, visible: true, frame: 0 };

    /**
     * How far each paper half moves (viewBox units) so the gap spans most of the stage
     * whatever its shape: on a tall phone screen the 1000×560 drawing is letterboxed.
     */
    const openShift = () => {
        const width = stage.clientWidth || 1;
        const height = stage.clientHeight || 1;
        const scale = Math.min(width / 1000, height / 560);
        const unitsTall = height / scale;
        const top = (560 - unitsTall) / 2;

        return [290 - (top + unitsTall * 0.17), top + unitsTall * 0.9 - 290];
    };

    stage.addEventListener('pointermove', (event) => {
        const rect = stage.getBoundingClientRect();

        state.lastPointer = performance.now();
        state.target = [clamp((event.clientX - rect.left) / rect.width) * 2 - 1, clamp((event.clientY - rect.top) / rect.height) * 2 - 1];
    });

    stage.addEventListener('pointerleave', () => {
        state.lastPointer = 0;
    });

    const progress = () => {
        if (reduced) {
            return 1;
        }

        const rect = root.getBoundingClientRect();
        const travel = rect.height - window.innerHeight;

        return travel <= 0 ? 1 : clamp(-rect.top / (travel * 0.85));
    };

    const render = (now) => {
        state.frame = 0;

        if (!state.visible) {
            return;
        }

        const p = progress();
        const crack = smooth(0.03, 0.2, p);
        const open = smooth(0.18, 0.62, p);
        const rise = smooth(0.26, 0.74, p);
        const pop = smooth(0.58, 0.88, p);
        const shake = smooth(0.16, 0.22, p) * (1 - smooth(0.26, 0.36, p));
        const k = Math.min(1, open * 4);

        if (now - state.lastPointer > 2200) {
            state.target = [Math.sin(now / 1500) * 0.45, Math.sin(now / 2100) * 0.25];
        }

        state.look = state.look.map((value, index) => value + (state.target[index] - value) * 0.1);

        const { line, fibres } = geometry;
        const fibreTop = line.concat(line.map(([x, y], i) => [x, y - fibres[i] * k]).reverse());
        const fibreBottom = line.concat(line.map(([x, y], i) => [x, y + fibres[(i + 7) % fibres.length] * k]).reverse());
        const [lookX, lookY] = state.look;
        const [up, down] = openShift();

        set('fibre-top', 'd', open > 0 ? toPath(fibreTop, true) : 'M0 0');
        set('fibre-bottom', 'd', open > 0 ? toPath(fibreBottom, true) : 'M0 0');
        set('shadow', 'stroke-opacity', (0.45 * Math.min(1, open * 3)).toFixed(3));
        set('crack', 'stroke-dashoffset', (geometry.length * (1 - crack)).toFixed(1));
        set('crack', 'opacity', (crack * (1 - Math.min(1, open * 3))).toFixed(3));
        set('shake', 'transform', `translate(${(Math.sin(now / 18) * 4 * shake).toFixed(2)} ${(Math.cos(now / 23) * 3 * shake).toFixed(2)})`);
        set('top', 'transform', `translate(${(-10 * open).toFixed(2)} ${(-up * open).toFixed(2)}) rotate(${(-3 * open).toFixed(3)} 500 290)`);
        set('bottom', 'transform', `translate(${(12 * open).toFixed(2)} ${(down * open).toFixed(2)}) rotate(${(2.4 * open).toFixed(3)} 500 290)`);

        if (scene && rig) {
            scene.style.opacity = Math.min(1, open * 2.5).toFixed(3);
            rig.style.setProperty('--spread', pop.toFixed(3));
            rig.style.transform = `perspective(1400px) translateY(${(18 * (1 - rise)).toFixed(2)}%) scale(${(0.88 + 0.12 * rise).toFixed(3)}) rotateY(${(lookX * 9).toFixed(2)}deg) rotateX(${(-lookY * 6).toFixed(2)}deg)`;
        }

        state.frame = requestAnimationFrame(render);
    };

    new IntersectionObserver(([entry]) => {
        state.visible = entry.isIntersecting;

        if (state.visible && !state.frame) {
            state.frame = requestAnimationFrame(render);
        }
    }).observe(root);

    state.frame = requestAnimationFrame(render);
};

export const initTearReveal = () => {
    document.querySelectorAll('[data-tear-reveal]').forEach((root) => {
        if (!root.dataset.tearMounted) {
            root.dataset.tearMounted = '1';
            mount(root);
        }
    });
};

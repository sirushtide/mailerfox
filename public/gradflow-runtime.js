(function(){
    var GRADFLOW_TYPE_NUMBER = {linear:0,conic:1,animated:2,wave:3,silk:4,smoke:5,stripe:6,mesh:7,aurora:8};
    var vs = `attribute vec2 position; varying vec2 vUv; void main() { vUv = position * 0.5 + 0.5; gl_Position = vec4(position, 0.0, 1.0); }`;
    var fs = `#ifdef GL_FRAGMENT_PRECISION_HIGH
precision highp float;
#else
precision mediump float;
#endif
uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_speed; uniform float u_scale; uniform int u_type; uniform float u_noise; uniform vec2 u_resolution; varying vec2 vUv;
#define PI 3.14159265359
float noise(vec2 st) { return fract(sin(dot(st, vec2(12.9898, 78.233))) * 43758.5453); } vec3 linearGradient(vec2 uv, float time) { float t = (uv.y * u_scale) + sin(uv.x * PI + time) * 0.1; t = clamp(t, 0.0, 1.0); return t < 0.5 ? mix(u_color1, u_color2, t * 2.0) : mix(u_color2, u_color3, (t - 0.5) * 2.0); } vec3 conicGradient(vec2 uv, float time) { vec2 center = vec2(0.5); vec2 pos = uv - center; float angle = atan(pos.y, pos.x); float normalizedAngle = (angle + PI) / (2.0 * PI); float t = fract(normalizedAngle * u_scale + time * 0.3); float smoothT = t; vec3 color; if (smoothT < 0.33) { color = mix(u_color1, u_color2, smoothstep(0.0, 0.33, smoothT)); } else if (smoothT < 0.66) { color = mix(u_color2, u_color3, smoothstep(0.33, 0.66, smoothT)); } else { color = mix(u_color3, u_color1, smoothstep(0.66, 1.0, smoothT)); } float dist = length(pos); color += sin(dist * 8.0 + time * 1.5) * 0.03; return color; }
#define S(a,b,t) smoothstep(a,b,t)
mat2 Rot(float a) { float s = sin(a); float c = cos(a); return mat2(c, -s, s, c); } vec2 hash(vec2 p) { p = vec2(dot(p, vec2(2127.1, 81.17)), dot(p, vec2(1269.5, 283.37))); return fract(sin(p) * 43758.5453); } float advancedNoise(in vec2 p) { vec2 i = floor(p); vec2 f = fract(p); vec2 u = f * f * (3.0 - 2.0 * f); float n = mix(mix(dot(-1.0 + 2.0 * hash(i + vec2(0.0, 0.0)), f - vec2(0.0, 0.0)), dot(-1.0 + 2.0 * hash(i + vec2(1.0, 0.0)), f - vec2(1.0, 0.0)), u.x), mix(dot(-1.0 + 2.0 * hash(i + vec2(0.0, 1.0)), f - vec2(0.0, 1.0)), dot(-1.0 + 2.0 * hash(i + vec2(1.0, 1.0)), f - vec2(1.0, 1.0)), u.x), u.y); return 0.5 + 0.5 * n; } vec3 animatedGradient(vec2 uv, float time) { float ratio = u_resolution.x / u_resolution.y; vec2 tuv = uv; tuv -= 0.5; float degree = advancedNoise(vec2(time * 0.1 * u_speed, tuv.x * tuv.y)); tuv.y *= 1.0 / ratio; tuv *= Rot(radians((degree - 0.5) * 720.0 * u_scale + 180.0)); tuv.y *= ratio; float frequency = 5.0 * u_scale; float amplitude = 30.0; float speed = time * 2.0 * u_speed; tuv.x += sin(tuv.y * frequency + speed) / amplitude; tuv.y += sin(tuv.x * frequency * 1.5 + speed) / (amplitude * 0.5); vec3 layer1 = mix(u_color1, u_color2, S(-0.3, 0.2, (tuv * Rot(radians(-5.0))).x)); vec3 layer2 = mix(u_color2, u_color3, S(-0.3, 0.2, (tuv * Rot(radians(-5.0))).x)); vec3 finalComp = mix(layer1, layer2, S(0.05, -0.2, tuv.y)); return finalComp; } vec3 waveGradient(vec2 uv, float time) { float y = uv.y; float wave1 = sin(uv.x * PI * u_scale * 0.8 + time * u_speed * 0.5) * 0.1; float wave2 = sin(uv.x * PI * u_scale * 0.5 + time * u_speed * 0.3) * 0.15; float wave3 = sin(uv.x * PI * u_scale * 1.2 + time * u_speed * 0.8) * 0.2; float flowingY = y + wave1 + wave2 + wave3; float pattern = smoothstep(0.0, 1.0, clamp(flowingY, 0.0, 1.0)); vec3 color; if (pattern < 0.33) { float t = smoothstep(0.0, 0.33, pattern); color = mix(u_color1, u_color2, t); } else if (pattern < 0.66) { float t = smoothstep(0.33, 0.66, pattern); color = mix(u_color2, u_color3, t); } else { float t = smoothstep(0.66, 1.0, pattern); color = mix(u_color3, u_color1, t); } float variation = sin(uv.x * PI * 2.0 + time * u_speed) * cos(uv.y * PI * 1.5 + time * u_speed * 0.7) * 0.02; color += variation; return clamp(color, 0.0, 1.0); } vec3 silkGradient(vec2 uv, float time) { vec2 fragCoord = uv * u_resolution; vec2 invResolution = 1.0 / u_resolution.xy; vec2 centeredUv = (fragCoord * 2.0 - u_resolution.xy) * invResolution; centeredUv *= u_scale; float dampening = 1.0 / (1.0 + u_scale * 0.1); float d = -time * u_speed * 0.5; float a = 0.0; for (float i = 0.0; i < 8.0; ++i) { a += cos(i - d - a * centeredUv.x) * dampening; d += sin(centeredUv.y * i + a) * dampening; } d += time * u_speed * 0.5; vec3 patterns = vec3( cos(centeredUv.x * d + a) * 0.5 + 0.5, cos(centeredUv.y * a + d) * 0.5 + 0.5, cos((centeredUv.x + centeredUv.y) * (d + a) * 0.5) * 0.5 + 0.5 ); vec3 color1Mix = mix(u_color1, u_color2, patterns.x); vec3 color2Mix = mix(u_color2, u_color3, patterns.y); vec3 color3Mix = mix(u_color3, u_color1, patterns.z); vec3 finalColor = mix(color1Mix, color2Mix, patterns.z); finalColor = mix(finalColor, color3Mix, patterns.x * 0.5); vec3 originalPattern = vec3(cos(centeredUv * vec2(d, a)) * 0.6 + 0.4, cos(a + d) * 0.5 + 0.5); originalPattern = cos(originalPattern * cos(vec3(d, a, 2.5)) * 0.5 + 0.5); return mix(finalColor, originalPattern * finalColor, 0.3); } vec3 smokeGradient(vec2 uv, float time) { float mr = min(u_resolution.x, u_resolution.y); vec2 fragCoord = uv * u_resolution; vec2 p = (2.0 * fragCoord.xy - u_resolution.xy) / mr; p *= u_scale; float iTime = time * u_speed; for(int i = 1; i < 10; i++) { vec2 newp = p; float fi = float(i); newp.x += 0.6 / fi * sin(fi * p.y + iTime + 0.3 * fi) + 1.0; newp.y += 0.6 / fi * sin(fi * p.x + iTime + 0.3 * (fi + 10.0)) - 1.4; p = newp; } float redPattern = 1.0; float greenPattern = 1.0 - sin(p.y); float bluePattern = sin(p.x + p.y); greenPattern = clamp(greenPattern, 0.0, 1.0); bluePattern = bluePattern * 0.5 + 0.5; vec3 color; vec3 color12 = mix(u_color1, u_color2, greenPattern); color = mix(color12, u_color3, bluePattern); return clamp(color, 0.0, 1.0); } float fbm(vec2 p) { float value = 0.0; float amplitude = 0.5; for (int i = 0; i < 4; i++) { value += amplitude * advancedNoise(p); p *= 2.0; amplitude *= 0.5; } return value; } vec3 meshGradient(vec2 uv, float time) { float ratio = u_resolution.x / u_resolution.y; vec2 p = vec2(uv.x * ratio, uv.y); float t = time * 0.6; vec2 c1 = vec2((0.3 + 0.25 * sin(t * 0.9)) * ratio, 0.4 + 0.3 * cos(t * 0.7)); vec2 c2 = vec2((0.7 + 0.25 * cos(t * 0.8)) * ratio, 0.6 + 0.3 * sin(t * 1.1)); vec2 c3 = vec2((0.5 + 0.3 * sin(t * 0.6 + 2.0)) * ratio, 0.5 + 0.35 * cos(t * 0.9 + 4.0)); float falloff = 1.5 + u_scale * 2.5; float w1 = exp(-falloff * dot(p - c1, p - c1)); float w2 = exp(-falloff * dot(p - c2, p - c2)); float w3 = exp(-falloff * dot(p - c3, p - c3)); float wBase = 0.08; vec3 base = mix(u_color1, u_color3, uv.y); vec3 color = (u_color1 * w1 + u_color2 * w2 + u_color3 * w3 + base * wBase) / (w1 + w2 + w3 + wBase); return clamp(color, 0.0, 1.0); } vec3 auroraGradient(vec2 uv, float time) { float t = time * 0.4; float curve = fbm(vec2(uv.x * 2.0 * u_scale + t * 0.5, t * 0.3)) - 0.5; float y = uv.y + curve * 0.5; float band = smoothstep(0.15, 0.55, y) * smoothstep(1.05, 0.6, y); float shimmer = fbm(vec2(uv.x * 5.0 * u_scale - t * 0.8, y * 3.0 + t * 0.5)); float intensity = band * (0.5 + 0.9 * shimmer); vec3 sky = mix(u_color2, u_color2 * 0.35, uv.y); vec3 curtain = mix(u_color1, u_color3, clamp(y + (shimmer - 0.5) * 0.6, 0.0, 1.0)); return clamp(sky + curtain * intensity, 0.0, 1.0); } vec3 stripeGradient(vec2 uv, float time) { vec2 p = ((uv * u_resolution * 2.0 - u_resolution.xy) / (u_resolution.x + u_resolution.y) * 2.0) * u_scale; float t = time * 0.7, a = 4.0 * p.y - sin(-p.x * 3.0 + p.y - t); a = smoothstep(cos(a) * 0.7, sin(a) * 0.7 + 1.0, cos(a - 4.0 * p.y) - sin(a + 3.0 * p.x)); vec2 warped = (cos(a) * p + sin(a) * vec2(-p.y, p.x)) * 0.5 + 0.5; vec3 color = mix(u_color1, u_color2, warped.x); color = mix(color, u_color3, warped.y); color *= color + 0.6 * sqrt(color); return clamp(color, 0.0, 1.0); } void main() { vec2 uv = vUv; float time = u_time * u_speed; vec3 color; if (u_type == 0) { color = linearGradient(uv, time); } else if (u_type == 1) { color = conicGradient(uv, time); } else if (u_type == 2) { color = animatedGradient(uv, time); } else if (u_type == 3) { color = waveGradient(uv, time); } else if (u_type == 4) { color = silkGradient(uv, time); } else if (u_type == 5) { color = smokeGradient(uv, time); } else if (u_type == 6) { color = stripeGradient(uv, time); } else if (u_type == 7) { color = meshGradient(uv, time); } else if (u_type == 8) { color = auroraGradient(uv, time); } else { color = animatedGradient(uv, time); } if (u_noise > 0.001) { float grain = noise(uv * 200.0 + time * 0.1); color *= (1.0 - u_noise * 0.4 + u_noise * grain * 0.4); } gl_FragColor = vec4(color, 1.0); }`;
    function compile(gl, source, type) {
        var s = gl.createShader(type);
        gl.shaderSource(s, source);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { gl.deleteShader(s); return null; }
        return s;
    }
    function hexToRgb(hex) {
        var h = parseInt(hex.replace('#', ''), 16);
        return [((h >> 16) & 255) / 255, ((h >> 8) & 255) / 255, (h & 255) / 255];
    }
    function initGradFlow(canvas) {
        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return;
        var vsh = compile(gl, vs, gl.VERTEX_SHADER);
        var fsh = compile(gl, fs, gl.FRAGMENT_SHADER);
        if (!vsh || !fsh) return;
        var program = gl.createProgram();
        gl.attachShader(program, vsh);
        gl.attachShader(program, fsh);
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return;
        var posBuf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,-1,1,1,-1,1,1]), gl.STATIC_DRAW);
        var posLoc = gl.getAttribLocation(program, 'position');
        var resLoc = gl.getUniformLocation(program, 'u_resolution');
        var timeLoc = gl.getUniformLocation(program, 'u_time');
        var c1Loc = gl.getUniformLocation(program, 'u_color1');
        var c2Loc = gl.getUniformLocation(program, 'u_color2');
        var c3Loc = gl.getUniformLocation(program, 'u_color3');
        var speedLoc = gl.getUniformLocation(program, 'u_speed');
        var scaleLoc = gl.getUniformLocation(program, 'u_scale');
        var typeLoc = gl.getUniformLocation(program, 'u_type');
        var noiseLoc = gl.getUniformLocation(program, 'u_noise');
        var colors = (canvas.dataset.mpColors || '#000000,#000000,#000000').split(',');
        var speed = parseFloat(canvas.dataset.mpSpeed) || 0.5;
        var scale = parseFloat(canvas.dataset.mpScale) || 1;
        var noise = parseFloat(canvas.dataset.mpNoise) || 0.3;
        var typeName = canvas.dataset.mpGfType || 'aurora';
        var typeNum = GRADFLOW_TYPE_NUMBER[typeName] !== undefined ? GRADFLOW_TYPE_NUMBER[typeName] : 8;
        var start = performance.now();
        function resize() {
            var rect = canvas.getBoundingClientRect();
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = Math.max(1, Math.floor(rect.width * dpr));
            canvas.height = Math.max(1, Math.floor(rect.height * dpr));
            gl.viewport(0, 0, canvas.width, canvas.height);
        }
        var ro = new ResizeObserver(resize);
        ro.observe(canvas);
        resize();
        function render() {
            var rect = canvas.getBoundingClientRect();
            if (rect.width && rect.height) {
                gl.viewport(0, 0, canvas.width, canvas.height);
                gl.useProgram(program);
                gl.enableVertexAttribArray(posLoc);
                gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
                gl.vertexAttribPointer(posLoc, 2, gl.FLOAT, false, 0, 0);
                gl.uniform2f(resLoc, canvas.width, canvas.height);
                gl.uniform1f(timeLoc, (performance.now() - start) / 1000);
                gl.uniform3fv(c1Loc, hexToRgb(colors[0]));
                gl.uniform3fv(c2Loc, hexToRgb(colors[1]));
                gl.uniform3fv(c3Loc, hexToRgb(colors[2]));
                gl.uniform1f(speedLoc, speed);
                gl.uniform1f(scaleLoc, scale);
                gl.uniform1i(typeLoc, typeNum);
                gl.uniform1f(noiseLoc, noise);
                gl.drawArrays(gl.TRIANGLES, 0, 6);
            }
            requestAnimationFrame(render);
        }
        render();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.mp-gradflow-canvas').forEach(initGradFlow);
        });
    } else {
        document.querySelectorAll('.mp-gradflow-canvas').forEach(initGradFlow);
    }
})();

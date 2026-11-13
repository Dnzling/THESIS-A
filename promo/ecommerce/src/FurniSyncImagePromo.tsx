import React from 'react';
import {
  AbsoluteFill,
  Easing,
  Img,
  interpolate,
  Sequence,
  spring,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {
  ArrowRight,
  Box,
  Check,
  ChevronRight,
  CircleCheck,
  Cuboid,
  MapPin,
  MousePointer2,
  PackageCheck,
  Rotate3D,
  Search,
  ShieldCheck,
  ShoppingBag,
  Sparkles,
  Truck,
  Zap,
} from 'lucide-react';
import './image-promo.css';

const ORANGE = '#ff6815';
const NAVY = '#10182b';

const assets = {
  logo: staticFile('assets/furnisync-logo.png'),
  landing: staticFile('assets/landing-page.png'),
  product: staticFile('assets/product-view.png'),
  product3d: staticFile('assets/product-3d-view.png'),
  cart: staticFile('assets/cart.png'),
  checkout: staticFile('assets/checkout.png'),
  tracking: staticFile('assets/tracking.png'),
};

const clamp = {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'} as const;

const sceneOpacity = (frame: number, duration: number) =>
  interpolate(frame, [0, 16, duration - 16, duration], [0, 1, 1, 0], clamp);

const enter = (frame: number, fps: number, delay = 0, distance = 55) => {
  const value = spring({
    frame: frame - delay,
    fps,
    config: {damping: 17, stiffness: 105, mass: 0.8},
  });
  return {
    opacity: value,
    transform: `translateY(${(1 - value) * distance}px) scale(${0.96 + value * 0.04})`,
  };
};

const BrandImage: React.FC<{inverse?: boolean; compact?: boolean}> = ({inverse = false, compact = false}) => (
  <div className={`ip-brand ${inverse ? 'inverse' : ''} ${compact ? 'compact' : ''}`}>
    <Img src={assets.logo}/>
  </div>
);

const Ambient: React.FC<{dark?: boolean}> = ({dark = false}) => {
  const frame = useCurrentFrame();
  const drift = Math.sin(frame / 45) * 22;
  return (
    <div className="ip-ambient" aria-hidden="true">
      <div className="ip-orb one" style={{transform: `translate(${drift}px, ${-drift * 0.4}px)`}}/>
      <div className="ip-orb two" style={{transform: `translate(${-drift}px, ${drift * 0.5}px)`}}/>
      <div className={`ip-grid ${dark ? 'dark' : ''}`}/>
      <div className="ip-grain"/>
    </div>
  );
};

const Eyebrow: React.FC<{children: React.ReactNode}> = ({children}) => (
  <div className="ip-eyebrow"><Sparkles size={18}/>{children}</div>
);

const Copy: React.FC<{
  frame: number;
  eyebrow: string;
  title: React.ReactNode;
  body: string;
  dark?: boolean;
}> = ({frame, eyebrow, title, body, dark = false}) => {
  const {fps} = useVideoConfig();
  return (
    <div className={`ip-copy ${dark ? 'on-dark' : ''}`}>
      <div style={enter(frame, fps)}><Eyebrow>{eyebrow}</Eyebrow></div>
      <h2 style={enter(frame, fps, 6)}>{title}</h2>
      <p style={enter(frame, fps, 12)}>{body}</p>
    </div>
  );
};

const Phone: React.FC<{
  src: string;
  frame: number;
  className?: string;
  delay?: number;
  rotate?: number;
  bare?: boolean;
}> = ({src, frame, className = '', delay = 0, rotate = 0, bare = false}) => {
  const {fps} = useVideoConfig();
  const value = spring({frame: frame - delay, fps, config: {damping: 15, stiffness: 90, mass: 0.9}});
  return (
    <div
      className={`ip-phone ${bare ? 'bare' : ''} ${className}`}
      style={{
        opacity: value,
        transform: `translateY(${(1 - value) * 110}px) rotate(${rotate * value}deg) scale(${0.88 + value * 0.12})`,
      }}
    >
      <div className="ip-phone-glow"/>
      <Img src={src}/>
    </div>
  );
};

const FloatingLabel: React.FC<{
  icon: React.ReactNode;
  children: React.ReactNode;
  frame: number;
  delay: number;
  className: string;
}> = ({icon, children, frame, delay, className}) => {
  const {fps} = useVideoConfig();
  const value = spring({frame: frame - delay, fps, config: {damping: 12, stiffness: 120}});
  const bob = Math.sin((frame - delay) / 20) * 6;
  return (
    <div className={`ip-floating-label ${className}`} style={{opacity: value, transform: `translateY(${(1 - value) * 35 + bob}px) scale(${0.8 + value * 0.2})`}}>
      <span>{icon}</span>{children}
    </div>
  );
};

const Opening: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const logo = spring({frame: frame - 8, fps, config: {damping: 16, stiffness: 85}});
  const ring = interpolate(frame, [20, 115], [0.72, 1.34], {...clamp, easing: Easing.out(Easing.cubic)});
  return (
    <AbsoluteFill className="ip-scene ip-opening" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient dark/>
      <div className="ip-opening-ring" style={{transform: `translate(-50%, -50%) scale(${ring})`, opacity: interpolate(frame, [20, 100], [0.7, 0], clamp)}}/>
      <div className="ip-opening-content">
        <div style={{opacity: logo, transform: `scale(${0.78 + logo * 0.22})`}}><BrandImage inverse/></div>
        <h1 style={enter(frame, fps, 18)}>Furniture shopping<br/><span>in perfect sync.</span></h1>
        <p style={enter(frame, fps, 28)}>Discover a smarter mobile experience built around every step of your furniture journey.</p>
        <div className="ip-scroll-cue" style={enter(frame, fps, 38)}><span>See the experience</span><ArrowRight size={22}/></div>
      </div>
    </AbsoluteFill>
  );
};

const Discover: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  const scroll = interpolate(frame, [70, 245], [0, -64], {...clamp, easing: Easing.inOut(Easing.cubic)});
  return (
    <AbsoluteFill className="ip-scene ip-light" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient/>
      <BrandImage compact/>
      <Copy frame={frame} eyebrow="Discover" title={<>Your showroom,<br/><span>always within reach.</span></>} body="Explore top-selling furniture and find pieces from trusted stores in one effortless mobile experience."/>
      <div className="ip-phone-stage discover">
        <div style={{transform: `translateY(${scroll}px)`}}><Phone src={assets.landing} frame={frame} rotate={2.5}/></div>
        <div className="ip-search-pulse" style={{opacity: interpolate(frame, [35, 70, 175, 210], [0, 1, 1, 0], clamp)}}><Search size={20}/> Search every collection</div>
        <FloatingLabel frame={frame} delay={42} className="top" icon={<Zap size={19}/>}>Top-selling picks</FloatingLabel>
        <FloatingLabel frame={frame} delay={64} className="bottom" icon={<CircleCheck size={19}/>}>Available now</FloatingLabel>
      </div>
      <div className="ip-step">01 <span/> DISCOVER</div>
    </AbsoluteFill>
  );
};

const Product: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const zoom = interpolate(frame, [90, 245], [1, 1.075], {...clamp, easing: Easing.inOut(Easing.cubic)});
  const cursorX = interpolate(frame, [45, 100], [500, 350], clamp);
  const cursorY = interpolate(frame, [45, 100], [210, 150], clamp);
  return (
    <AbsoluteFill className="ip-scene ip-product-scene" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient dark/>
      <Copy dark frame={frame} eyebrow="Explore" title={<>Details that make<br/><span>decisions easier.</span></>} body="Inspect every finish, browse product photos, and move from inspiration to confidence."/>
      <div className="ip-product-phone" style={{transform: `scale(${zoom})`}}><Phone src={assets.product} frame={frame} rotate={-1.5}/></div>
      <div className="ip-cursor" style={{left: cursorX, top: cursorY, opacity: interpolate(frame, [34, 52, 108, 124], [0, 1, 1, 0], clamp)}}><MousePointer2 size={34} fill="white"/></div>
      <div className="ip-feature-stack" style={enter(frame, fps, 38)}>
        <div><span><Search/></span><b>High-resolution gallery</b><small>See materials and finishes clearly.</small></div>
        <div><span><Cuboid/></span><b>Interactive 3D</b><small>Move beyond static product photos.</small></div>
        <div><span><ShieldCheck/></span><b>Shop with confidence</b><small>Make a more informed choice.</small></div>
      </div>
      <div className="ip-step inverse">02 <span/> EXPLORE</div>
    </AbsoluteFill>
  );
};

const ThreeD: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const switchAt = 82;
  const photoOpacity = interpolate(frame, [switchAt - 14, switchAt + 14], [1, 0], clamp);
  const modelOpacity = interpolate(frame, [switchAt - 8, switchAt + 20], [0, 1], clamp);
  const rotate = interpolate(frame, [90, 225], [-4, 5], {...clamp, easing: Easing.inOut(Easing.cubic)});
  const sweep = interpolate(frame, [78, 135], [-110, 110], clamp);
  return (
    <AbsoluteFill className="ip-scene ip-3d-scene" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient/>
      <BrandImage compact/>
      <div className="ip-3d-title" style={enter(frame, fps)}><Eyebrow>View in 3D</Eyebrow><h2>From a photo<br/>to a <span>new perspective.</span></h2></div>
      <div className="ip-morph-stage" style={{transform: `perspective(1400px) rotateY(${rotate}deg)`}}>
        <div style={{opacity: photoOpacity}}><Phone src={assets.product} frame={frame} bare/></div>
        <div className="ip-model-layer" style={{opacity: modelOpacity}}><Phone src={assets.product3d} frame={frame} bare/></div>
        <div className="ip-light-sweep" style={{transform: `translateX(${sweep}%) rotate(12deg)`, opacity: interpolate(frame, [70, 88, 128, 145], [0, 0.8, 0.8, 0], clamp)}}/>
      </div>
      <div className="ip-rotate-control" style={enter(frame, fps, 106)}><Rotate3D size={24}/><div><b>Rotate. Inspect. Decide.</b><small>Experience furniture beyond the frame.</small></div></div>
      <div className="ip-drag-ring" style={{opacity: interpolate(frame, [100, 125, 205, 225], [0, 1, 1, 0], clamp)}}><span>↔</span></div>
      <div className="ip-step">03 <span/> EXPERIENCE</div>
    </AbsoluteFill>
  );
};

const Cart: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const count = Math.round(interpolate(frame, [72, 130], [1, 2], clamp));
  return (
    <AbsoluteFill className="ip-scene ip-cart-scene" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient dark/>
      <Copy dark frame={frame} eyebrow="Build your space" title={<>Everything you love,<br/><span>ready when you are.</span></>} body="Manage products, adjust quantities, and review every choice from a clear, organized cart."/>
      <div className="ip-cart-phone"><Phone src={assets.cart} frame={frame} bare/></div>
      <div className="ip-cart-bubble first" style={enter(frame, fps, 48)}><ShoppingBag size={22}/><span><b>2 products</b><small>Gray Area Furnitures</small></span></div>
      <div className="ip-cart-bubble quantity" style={enter(frame, fps, 75)}><span>Quantity</span><b>− &nbsp; {count} &nbsp; +</b></div>
      <div className="ip-cart-bubble total" style={enter(frame, fps, 105)}><small>Cart value</small><b>₱91,887.00</b><Check size={20}/></div>
      <div className="ip-step inverse">04 <span/> CART</div>
    </AbsoluteFill>
  );
};

const Checkout: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const progress = interpolate(frame, [35, 190], [0.34, 0.72], {...clamp, easing: Easing.out(Easing.cubic)});
  return (
    <AbsoluteFill className="ip-scene ip-checkout-scene" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient/>
      <BrandImage compact/>
      <div className="ip-checkout-stage"><Phone src={assets.checkout} frame={frame} rotate={1.3}/><div className="ip-delivery-glow" style={{opacity: interpolate(frame, [74, 105, 180, 215], [0, 1, 1, 0], clamp)}}/></div>
      <div className="ip-checkout-copy">
        <Copy frame={frame} eyebrow="Checkout" title={<>Clear choices.<br/><span>Confident checkout.</span></>} body="Choose pickup or truck delivery, confirm the details, and place your order with full visibility."/>
        <div className="ip-progress-card" style={enter(frame, fps, 38)}>
          <div><Check size={17}/> Address</div><div className="active">2 &nbsp; Service</div><div>3 &nbsp; Payment</div>
          <span><i style={{transform: `scaleX(${progress})`}}/></span>
        </div>
        <div className="ip-secure" style={enter(frame, fps, 68)}><ShieldCheck size={22}/><span><b>Secure and transparent</b><small>Know the cost before confirming.</small></span></div>
      </div>
      <div className="ip-step">05 <span/> CHECKOUT</div>
    </AbsoluteFill>
  );
};

const Tracking: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const pulse = 1 + Math.sin(frame / 7) * 0.14;
  const route = interpolate(frame, [55, 195], [0, 1], {...clamp, easing: Easing.inOut(Easing.cubic)});
  return (
    <AbsoluteFill className="ip-scene ip-tracking-scene" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient dark/>
      <div className="ip-track-copy">
        <div style={enter(frame, fps)}><Eyebrow>Live delivery</Eyebrow></div>
        <h2 style={enter(frame, fps, 6)}>From the store<br/>to <span>your doorstep.</span></h2>
        <p style={enter(frame, fps, 12)}>Follow your furniture in real time and stay informed throughout the final mile.</p>
        <div className="ip-live-card" style={enter(frame, fps, 40)}><div className="ip-live-dot" style={{transform: `scale(${pulse})`}}/><span><b>Delivery is moving</b><small>Live route updates</small></span><ChevronRight/></div>
      </div>
      <div className="ip-track-phone"><Phone src={assets.tracking} frame={frame} rotate={-1}/><div className="ip-map-focus" style={{opacity: interpolate(frame, [45, 75], [0, 1], clamp), transform: `scale(${0.8 + route * 0.2})`}}><MapPin size={25} fill={ORANGE}/></div></div>
      <div className="ip-route-card" style={enter(frame, fps, 68)}><div><Truck/></div><span><small>LIVE STATUS</small><b>On the way</b></span><PackageCheck/></div>
      <div className="ip-step inverse">06 <span/> TRACK</div>
    </AbsoluteFill>
  );
};

const Closing: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const flare = interpolate(frame, [0, 115], [-60, 70], clamp);
  return (
    <AbsoluteFill className="ip-scene ip-closing" style={{opacity: sceneOpacity(frame, durationInFrames)}}>
      <Ambient dark/>
      <div className="ip-closing-flare" style={{transform: `translateX(${flare}%) rotate(18deg)`}}/>
      <div className="ip-closing-content">
        <div style={enter(frame, fps)}><BrandImage inverse/></div>
        <h2 style={enter(frame, fps, 6)}>Discover. Experience.<br/><span>Bring it home.</span></h2>
        <p style={enter(frame, fps, 12)}>A smarter furniture journey, from the first look to the final delivery.</p>
        <div className="ip-closing-button" style={enter(frame, fps, 20)}>Experience FurniSync <ArrowRight size={23}/></div>
      </div>
      <div className="ip-closing-icons">
        {[Search, Cuboid, ShoppingBag, Truck].map((Icon, index) => <div key={index} style={enter(frame, fps, 30 + index * 6)}><Icon/></div>)}
      </div>
    </AbsoluteFill>
  );
};

export const FurniSyncImagePromo: React.FC = () => (
  <AbsoluteFill className="ip-root">
    <Sequence durationInFrames={150}><Opening/></Sequence>
    <Sequence from={150} durationInFrames={270}><Discover/></Sequence>
    <Sequence from={420} durationInFrames={270}><Product/></Sequence>
    <Sequence from={690} durationInFrames={240}><ThreeD/></Sequence>
    <Sequence from={930} durationInFrames={240}><Cart/></Sequence>
    <Sequence from={1170} durationInFrames={240}><Checkout/></Sequence>
    <Sequence from={1410} durationInFrames={240}><Tracking/></Sequence>
    <Sequence from={1650} durationInFrames={150}><Closing/></Sequence>
  </AbsoluteFill>
);
